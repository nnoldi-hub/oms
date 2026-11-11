<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\FoodFundTransaction;
use App\Models\Menu;
use App\Models\Volunteer;
use App\Models\Week;
use App\Models\WorkTeam;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "Programeaza perioada": creeaza sau ajusteaza programul de mese pe un interval,
 * pastrand rotatia retetelor (minimum 10 zile intre acelasi fel principal), doua
 * ciorbe diferite pe pozitiile 2 si 4 ale saptamanii si echilibrarea costului
 * intre saptamani.
 *
 * - o saptamana = zilele lucratoare dintr-un interval luni-duminica; prima poate
 *   fi incompleta daca inceputul nu este luni;
 * - "inlocuieste tot" sterge programul existent (in afara de zilele protejate) si
 *   creeaza programul nou;
 * - "ajusteaza perioada" adauga/scoate zile doar la capete; zilele din interior
 *   raman neschimbate si primesc meniuri doar zilele noi;
 * - zilele protejate (portii confirmate, gatit, cost real, persoane, echipe,
 *   contributii sau tranzactii de fond) nu se sterg decat cu confirmare explicita.
 */
class PeriodProgramPlanner
{
    private const PROBE_PORTIONS = 100;

    private const MAX_DAYS = 366;

    /** Zile libere legale cu data fixa (luna-zi). */
    public const LEGAL_HOLIDAYS = ['01-01', '01-02', '01-06', '01-07', '01-24', '05-01', '12-01', '12-25', '12-26'];

    public function __construct(private DailyMealCostCalculator $costCalculator) {}

    /**
     * @return list<string>
     */
    public static function suggestedHolidays(CarbonInterface $from, CarbonInterface $to): array
    {
        $from = CarbonImmutable::parse($from)->startOfDay();
        $to = CarbonImmutable::parse($to)->startOfDay();
        $dates = [];

        for ($year = $from->year; $year <= $to->year; $year++) {
            foreach (self::LEGAL_HOLIDAYS as $monthDay) {
                $date = CarbonImmutable::parse($year.'-'.$monthDay)->startOfDay();

                if ($date->betweenIncluded($from, $to)) {
                    $dates[] = $date->toDateString();
                }
            }
        }

        sort($dates);

        return $dates;
    }

    /**
     * @param  array{from: string, to: string, holidays?: list<string>, include_saturday?: bool, mode?: string, reuse_menus?: bool, delete_protected?: bool}  $input
     * @return array<string, mixed>
     */
    public function preview(array $input): array
    {
        return $this->buildPlan($input)['preview'];
    }

    /**
     * @param  array{from: string, to: string, holidays?: list<string>, include_saturday?: bool, mode?: string, reuse_menus?: bool, delete_protected?: bool}  $input
     * @return array<string, mixed>
     */
    public function apply(array $input): array
    {
        $plan = $this->buildPlan($input);

        if ($plan['error'] !== null) {
            return $plan['preview'];
        }

        $applied = DB::transaction(fn (): array => $this->writePlan($plan));

        return array_merge($plan['preview'], ['applied' => $applied]);
    }

    /**
     * @param  array{from: string, to: string, holidays?: list<string>, include_saturday?: bool, mode?: string, reuse_menus?: bool, delete_protected?: bool}  $input
     * @return array{error: ?string, preview: array<string, mixed>, write: ?array<string, mixed>}
     */
    private function buildPlan(array $input): array
    {
        $from = CarbonImmutable::parse($input['from'])->startOfDay();
        $to = CarbonImmutable::parse($input['to'])->startOfDay();

        if ($to->lt($from)) {
            return $this->errorPlan('Data de sfarsit trebuie sa fie dupa data de inceput.');
        }

        if ($from->diffInDays($to) > self::MAX_DAYS) {
            return $this->errorPlan('Intervalul este prea mare. Alege cel mult un an.');
        }

        $mode = ($input['mode'] ?? 'replace') === 'adjust' ? 'adjust' : 'replace';
        $includeSaturday = (bool) ($input['include_saturday'] ?? false);
        $reuseMenus = (bool) ($input['reuse_menus'] ?? true);
        $deleteProtected = (bool) ($input['delete_protected'] ?? false);
        $holidays = collect($input['holidays'] ?? [])
            ->map(fn ($date): string => CarbonImmutable::parse($date)->toDateString())
            ->filter(fn (string $date): bool => $date >= $from->toDateString() && $date <= $to->toDateString())
            ->unique()
            ->values()
            ->all();

        $congregations = Congregation::query()->orderBy('id')->get();

        if ($congregations->isEmpty()) {
            return $this->errorPlan('Nu exista congregatii configurate.');
        }

        $mains = Menu::query()->where('type', 'main')->where('is_active', true)->orderBy('id')->get()->keyBy('id');

        if ($mains->isEmpty()) {
            return $this->errorPlan('Catalogul nu are feluri principale active.');
        }

        $soups = Menu::query()->where('type', 'soup')->where('is_active', true)->orderBy('id')->get()->keyBy('id');
        $entries = $this->entries($from, $to, $holidays, $includeSaturday);

        if ($entries === []) {
            return $this->errorPlan('Intervalul ales nu contine zile lucratoare.');
        }

        $existing = DailyMeal::query()
            ->with(['week', 'menu', 'soupMenu'])
            ->orderBy('meal_date')
            ->orderBy('id')
            ->get();
        $childCounts = $this->childCounts();
        $existingByDate = $existing->keyBy(fn (DailyMeal $meal): string => $meal->meal_date->toDateString());

        $deletionMeals = collect();
        $days = [];

        if ($mode === 'replace') {
            foreach ($existing as $meal) {
                if ($this->isProtected($meal, $childCounts) && ! $deleteProtected) {
                    continue;
                }

                $deletionMeals->push($meal);
            }

            $deletedIds = $deletionMeals->pluck('id')->all();
            $kept = $existing->reject(fn (DailyMeal $meal): bool => in_array($meal->id, $deletedIds, true));

            foreach ($entries as $entry) {
                $sameDate = $existingByDate[$entry['date']] ?? null;

                if ($sameDate !== null && $kept->contains('id', $sameDate->id)) {
                    $days[$entry['date']] = $this->dayFromExisting($sameDate, true);

                    continue;
                }

                $day = $this->newDay($entry);
                $day['status'] = 'new';

                if ($reuseMenus && $sameDate !== null) {
                    $mainId = $mains->has($sameDate->menu_id) ? $sameDate->menu_id : null;
                    $soupId = $soups->has($sameDate->soup_menu_id) ? $sameDate->soup_menu_id : null;
                    $day['main_id'] = $mainId;
                    $day['soup_id'] = $soupId;
                    $day['reused'] = $mainId !== null || $soupId !== null;
                }

                $days[$entry['date']] = $day;
            }

            foreach ($kept as $meal) {
                $days[$meal->meal_date->toDateString()] ??= $this->dayFromExisting($meal, true);
            }
        } else {
            $deletionMeals = $existing
                ->filter(function (DailyMeal $meal) use ($from, $to, $deleteProtected, $childCounts): bool {
                    $outside = $meal->meal_date->lt($from) || $meal->meal_date->gt($to);

                    return $outside && ($deleteProtected || ! $this->isProtected($meal, $childCounts));
                })
                ->values();

            $deletedIds = $deletionMeals->pluck('id')->all();
            $kept = $existing->reject(fn (DailyMeal $meal): bool => in_array($meal->id, $deletedIds, true))->values();

            foreach ($kept as $meal) {
                $days[$meal->meal_date->toDateString()] = $this->dayFromExisting($meal, $this->isProtected($meal, $childCounts));
            }

            $keptInRange = $kept
                ->filter(fn (DailyMeal $meal): bool => $meal->meal_date->betweenIncluded($from, $to))
                ->values();

            if ($keptInRange->isEmpty()) {
                foreach ($entries as $entry) {
                    $days[$entry['date']] = $this->newDay($entry);
                }
            } else {
                $keptDates = $keptInRange
                    ->pluck('meal_date')
                    ->map(fn ($date): string => CarbonImmutable::parse($date)->toDateString());
                $minDate = $keptDates->min();
                $maxDate = $keptDates->max();

                foreach ($entries as $entry) {
                    if (isset($days[$entry['date']])) {
                        continue;
                    }

                    if ($entry['date'] < $minDate || $entry['date'] > $maxDate) {
                        $days[$entry['date']] = $this->newDay($entry);
                    }
                }
            }
        }

        ksort($days);

        if ($days === []) {
            return $this->errorPlan('Nu a rezultat nicio zi pentru program.');
        }

        $weekModels = Week::query()->get()->keyBy('id');
        $weeks = $this->groupWeeks($days, $weekModels, $includeSaturday);

        $orderedCongregations = $congregations->pluck('id')->values()->all();

        foreach ($weeks as $index => &$week) {
            if ($week['congregation_id'] === null) {
                $week['congregation_id'] = $orderedCongregations[$index % count($orderedCongregations)];
            }

            foreach ($week['day_dates'] as $date) {
                if ($days[$date]['status'] === 'new') {
                    $days[$date]['congregation_id'] = $week['congregation_id'];
                }
            }
        }

        unset($week);

        $usedMenuIds = collect($days)
            ->flatMap(fn (array $day): array => [$day['main_id'], $day['soup_id']])
            ->filter()
            ->unique()
            ->values()
            ->all();
        $costMenus = Menu::query()
            ->whereIn('id', array_values(array_unique(array_merge($mains->keys()->all(), $soups->keys()->all(), $usedMenuIds))))
            ->get()
            ->keyBy('id');
        $menuCosts = $this->portionCosts($costMenus);
        $mainCosts = array_intersect_key($menuCosts, $mains->all());
        $soupCosts = array_intersect_key($menuCosts, $soups->all());
        $averageMain = $this->average($mainCosts);
        $averageSoup = $this->average($soupCosts);

        $this->assignMains($days, $weeks, $mains, $mainCosts, $averageMain);
        $this->assignSoups($days, $weeks, $soups, $menuCosts, $averageSoup, $mains);

        $menuNames = Menu::query()->pluck('name', 'id');
        $congregationNames = $congregations->pluck('name', 'id');

        $previewWeeks = [];
        $hasMissingPrices = in_array(null, $menuCosts, true);
        $weekCosts = [];

        foreach ($weeks as $week) {
            $dayPlans = [];
            $costSum = 0.0;
            $costDays = 0;
            $first = null;
            $last = null;

            foreach ($week['day_dates'] as $date) {
                $day = $days[$date];
                $first ??= $day['day'];
                $last = $day['day'];

                $cost = $this->costOf($menuCosts, $day['main_id'], $averageMain) + $this->costOf($menuCosts, $day['soup_id'], $averageSoup);

                if (! $day['free']) {
                    $costSum += $cost;
                    $costDays++;

                    if ($day['main_id'] !== null && ($menuCosts[$day['main_id']] ?? null) === null) {
                        $hasMissingPrices = true;
                    }

                    if ($day['soup_id'] !== null && ($menuCosts[$day['soup_id']] ?? null) === null) {
                        $hasMissingPrices = true;
                    }
                }

                $dayPlans[] = [
                    'date' => $date,
                    'congregation' => $congregationNames[$day['congregation_id']] ?? null,
                    'main' => $day['main_id'] !== null ? ($menuNames[$day['main_id']] ?? null) : null,
                    'soup' => $day['soup_id'] !== null ? ($menuNames[$day['soup_id']] ?? null) : null,
                    'is_free_day' => $day['free'],
                    'status' => $day['status'],
                    'protected' => $day['status'] === 'protected',
                    'reused' => (bool) $day['reused'],
                    'cost_per_portion' => $day['free'] ? 0.0 : round($cost, 2),
                ];
            }

            $weekCosts[] = $costDays === 0 ? 0.0 : round($costSum / $costDays, 2);

            $previewWeeks[] = [
                'week_number' => null,
                'week_id' => $week['week_id'],
                'start' => $first?->toDateString(),
                'end' => $last?->toDateString(),
                'congregation_id' => $week['congregation_id'],
                'congregation' => $congregationNames[$week['congregation_id']] ?? null,
                'cost_per_portion' => $costDays === 0 ? 0.0 : round($costSum / $costDays, 2),
                'days' => $dayPlans,
            ];
        }

        foreach ($previewWeeks as $index => &$weekPlan) {
            $weekPlan['week_number'] = $index + 1;
        }

        unset($weekPlan);

        $finalWeekIds = collect($weeks)->pluck('week_id')->filter()->map(fn ($id): int => (int) $id)->all();
        $deletedWeekIds = $weekModels->keys()->map(fn ($id): int => (int) $id)->diff($finalWeekIds)->values();
        $deletedWeeks = $this->deletionPreview($deletionMeals, $menuNames);
        $protected = $this->protectedPreview($existing, $childCounts, $menuNames, $deleteProtected);

        $newDays = collect($days)->where('status', 'new')->count();
        $keptDays = count($days) - $newDays;

        $preview = [
            'error' => null,
            'mode' => $mode,
            'weeks' => $previewWeeks,
            'deleted' => $deletedWeeks,
            'protected' => $protected,
            'delete_protected' => $deleteProtected,
            'spread' => $weekCosts === [] ? 0.0 : round(max($weekCosts) - min($weekCosts), 2),
            'has_missing_prices' => $hasMissingPrices,
            'created_weeks' => count($weeks) - count(array_filter($weeks, fn (array $week): bool => $week['week_id'] !== null)),
            'created_days' => $newDays,
            'deleted_weeks' => $deletedWeekIds->count(),
            'deleted_days' => $deletionMeals->count(),
            'kept_days' => $keptDays,
        ];

        $write = [
            'deletion_ids' => $deletionMeals->pluck('id')->values()->all(),
            'weeks' => collect($weeks)->map(fn (array $week): array => [
                'key' => $week['key'],
                'week_id' => $week['week_id'],
                'start' => $week['start']->toDateString(),
                'include_saturday' => $week['include_saturday'],
                'congregation_id' => $week['congregation_id'],
                'day_dates' => $week['day_dates'],
            ])->values()->all(),
            'days' => collect($days)->map(fn (array $day): array => [
                'date' => $day['date'],
                'plan_week_key' => $day['plan_week_key'],
                'existing_id' => $day['existing_id'],
                'status' => $day['status'],
                'week_id' => $day['week_id'],
                'congregation_id' => $day['congregation_id'],
                'free' => $day['free'],
                'main_id' => $day['main_id'],
                'soup_id' => $day['soup_id'],
            ])->values()->all(),
            'counts' => [
                'created_weeks' => $preview['created_weeks'],
                'created_days' => $preview['created_days'],
                'deleted_weeks' => $preview['deleted_weeks'],
                'deleted_days' => $preview['deleted_days'],
                'kept_days' => $preview['kept_days'],
            ],
        ];

        return ['error' => null, 'preview' => $preview, 'write' => $write];
    }

    /**
     * @param  list<array{date: string, day: CarbonImmutable, free: bool}>  $entries
     * @return list<array{date: string, day: CarbonImmutable, free: bool}>
     */
    private function entries(CarbonImmutable $from, CarbonImmutable $to, array $holidays, bool $includeSaturday): array
    {
        $entries = [];
        $holidays = array_flip($holidays);

        for ($cursor = $from; $cursor->lte($to); $cursor = $cursor->addDay()) {
            $weekday = (int) $cursor->isoWeekday();
            $works = $weekday <= 5 || ($includeSaturday && $weekday === 6);

            if (! $works) {
                continue;
            }

            $entries[] = [
                'date' => $cursor->toDateString(),
                'day' => $cursor,
                'free' => isset($holidays[$cursor->toDateString()]),
            ];
        }

        return $entries;
    }

    /**
     * @param  array{date: string, day: CarbonImmutable, free: bool}  $entry
     * @return array<string, mixed>
     */
    private function newDay(array $entry): array
    {
        return [
            'date' => $entry['date'],
            'day' => $entry['day'],
            'free' => $entry['free'],
            'status' => 'new',
            'existing_id' => null,
            'week_id' => null,
            'week_key' => $entry['day']->format('o-W'),
            'congregation_id' => null,
            'main_id' => null,
            'soup_id' => null,
            'reused' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dayFromExisting(DailyMeal $meal, bool $protected): array
    {
        return [
            'date' => $meal->meal_date->toDateString(),
            'day' => CarbonImmutable::parse($meal->meal_date),
            'free' => (bool) $meal->is_free_day,
            'status' => $protected ? 'protected' : 'existing',
            'existing_id' => $meal->id,
            'week_id' => $meal->week_id,
            'week_key' => $meal->meal_date->format('o-W'),
            'congregation_id' => $meal->congregation_id,
            'main_id' => $meal->menu_id,
            'soup_id' => $meal->soup_menu_id,
            'reused' => false,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $days
     * @param  Collection<int, Week>  $weekModels
     * @return list<array<string, mixed>>
     */
    private function groupWeeks(array &$days, Collection $weekModels, bool $includeSaturday): array
    {
        $weeks = [];

        foreach ($days as $date => &$day) {
            if ($day['week_id'] === null) {
                continue;
            }

            $key = 'week-'.$day['week_id'];
            $model = $weekModels->get($day['week_id']);

            if (! isset($weeks[$key])) {
                $weeks[$key] = [
                    'key' => $key,
                    'week_id' => (int) $day['week_id'],
                    'start' => $day['day']->startOfWeek(CarbonInterface::MONDAY),
                    'include_saturday' => $model?->include_saturday ?? $includeSaturday,
                    'congregation_id' => $model?->congregation_id,
                    'iso' => $day['week_key'],
                    'day_dates' => [],
                ];
            }

            $weeks[$key]['start'] = $this->earliest($weeks[$key]['start'], $day['day']->startOfWeek(CarbonInterface::MONDAY));
            $weeks[$key]['day_dates'][] = $date;
            $day['plan_week_key'] = $key;
        }

        unset($day);

        foreach ($days as $date => &$day) {
            if ($day['week_id'] !== null) {
                continue;
            }

            $key = null;

            foreach ($weeks as $candidateKey => $week) {
                if ($week['iso'] === $day['week_key']) {
                    $key = $candidateKey;

                    break;
                }
            }

            if ($key === null) {
                $key = 'iso-'.$day['week_key'];
                $weeks[$key] = [
                    'key' => $key,
                    'week_id' => null,
                    'start' => $day['day']->startOfWeek(CarbonInterface::MONDAY),
                    'include_saturday' => $includeSaturday,
                    'congregation_id' => null,
                    'iso' => $day['week_key'],
                    'day_dates' => [],
                ];
            }

            $weeks[$key]['day_dates'][] = $date;
            $day['plan_week_key'] = $key;
        }

        unset($day);

        $weeks = array_values($weeks);

        foreach ($weeks as &$week) {
            usort($week['day_dates'], fn (string $left, string $right): int => $left <=> $right);
        }

        unset($week);

        usort($weeks, fn (array $left, array $right): int => $left['start']->toDateString() <=> $right['start']->toDateString());

        return $weeks;
    }

    private function earliest(CarbonImmutable $left, CarbonImmutable $right): CarbonImmutable
    {
        return $left->lte($right) ? $left : $right;
    }

    /**
     * @param  array<string, array<string, mixed>>  $days
     * @param  list<array<string, mixed>>  $weeks
     * @param  Collection<int, Menu>  $mains
     * @param  array<int, ?float>  $mainCosts
     */
    private function assignMains(array &$days, array $weeks, Collection $mains, array $mainCosts, float $averageMain): void
    {
        $usage = [];

        foreach ($days as $day) {
            if (! $day['free'] && $day['main_id'] !== null) {
                $usage[$day['main_id']][] = $day['day'];
            }
        }

        foreach ($weeks as $week) {
            $weekSum = 0.0;
            $slot = 0;

            foreach ($week['day_dates'] as $date) {
                if ($days[$date]['free']) {
                    continue;
                }

                if ($days[$date]['status'] === 'new' && $days[$date]['main_id'] === null) {
                    $day = $days[$date]['day'];
                    $ranked = $mains->keys()
                        ->map(fn (int $id): array => ['id' => $id, 'distance' => $this->distanceToUsage($usage[$id] ?? [], $day)])
                        ->sortBy([['distance', 'desc'], ['id', 'asc']])
                        ->values();

                    $allowed = $ranked
                        ->filter(fn (array $candidate): bool => $candidate['distance'] >= MenuSchedulingValidator::MINIMUM_DAYS_BETWEEN_MAIN_RECIPES)
                        ->values();
                    $pool = $allowed->isNotEmpty() ? $allowed : $ranked->take(1);

                    $shortlist = $pool->take(max(3, (int) ceil($pool->count() / 2)));
                    $target = $averageMain * ($slot + 1);

                    $chosen = $shortlist
                        ->sortBy(fn (array $candidate): float => abs($weekSum + $this->costOf($mainCosts, $candidate['id'], $averageMain) - $target))
                        ->first();

                    if ($chosen !== null) {
                        $days[$date]['main_id'] = $chosen['id'];
                        $usage[$chosen['id']][] = $day;
                    }
                }

                $weekSum += $this->costOf($mainCosts, $days[$date]['main_id'], $averageMain);
                $slot++;
            }
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $days
     * @param  list<array<string, mixed>>  $weeks
     * @param  Collection<int, Menu>  $soups
     * @param  array<int, ?float>  $menuCosts
     * @param  Collection<int, Menu>  $mains
     */
    private function assignSoups(array &$days, array $weeks, Collection $soups, array $menuCosts, float $averageSoup, Collection $mains): void
    {
        if ($soups->isEmpty()) {
            return;
        }

        $history = [];

        foreach ($days as $day) {
            if (! $day['free'] && $day['soup_id'] !== null) {
                $history[] = ['date' => $day['day'], 'id' => (int) $day['soup_id']];
            }
        }

        $ingredients = [];

        foreach ($mains as $menu) {
            $ingredients[$menu->id] = $this->ingredientNames($menu);
        }

        foreach ($soups as $menu) {
            $ingredients[$menu->id] = $this->ingredientNames($menu);
        }

        foreach ($weeks as $week) {
            $weekSoups = [];
            $weekSum = 0.0;
            $placed = 0;

            foreach ($week['day_dates'] as $date) {
                if (! $days[$date]['free'] && $days[$date]['soup_id'] !== null) {
                    $weekSoups[] = (int) $days[$date]['soup_id'];
                    $weekSum += $this->costOf($menuCosts, $days[$date]['soup_id'], $averageSoup);
                    $placed++;
                }
            }

            $position = 0;

            foreach ($week['day_dates'] as $date) {
                if ($days[$date]['free']) {
                    continue;
                }

                $hostsSoup = in_array($position, MealRotation::SOUP_SLOTS_PER_WEEK, true);

                if ($hostsSoup && $days[$date]['status'] === 'new' && $days[$date]['soup_id'] === null && count($weekSoups) < MenuSchedulingValidator::SOUP_LIMIT_PER_WEEK) {
                    $day = $days[$date]['day'];
                    $previousSoup = $this->lastSoup($history, $day);
                    $mainIngredients = $ingredients[$days[$date]['main_id'] ?? 0] ?? [];

                    $candidates = $soups->keys()
                        ->reject(fn (int $id): bool => in_array($id, $weekSoups, true) || $id === $previousSoup)
                        ->values();

                    if ($candidates->isEmpty()) {
                        $candidates = $soups->keys()
                            ->reject(fn (int $id): bool => in_array($id, $weekSoups, true))
                            ->values();
                    }

                    if ($candidates->isNotEmpty()) {
                        $withoutSharedIngredient = $candidates
                            ->filter(fn (int $id): bool => array_intersect($mainIngredients, $ingredients[$id] ?? []) === [])
                            ->values();

                        $pool = $withoutSharedIngredient->isNotEmpty() ? $withoutSharedIngredient : $candidates;
                        $lastUsed = $this->lastSoupUsage($history, $day);

                        $shortlist = $pool
                            ->sortBy([fn (int $left, int $right): int => ($lastUsed[$left] ?? '') <=> ($lastUsed[$right] ?? ''), fn (int $left, int $right): int => $left <=> $right])
                            ->take(2);
                        $target = $averageSoup * ($placed + 1);

                        $chosen = $shortlist
                            ->sortBy(fn (int $id): float => abs($weekSum + $this->costOf($menuCosts, $id, $averageSoup) - $target))
                            ->first();

                        if ($chosen !== null) {
                            $days[$date]['soup_id'] = $chosen;
                            $weekSoups[] = $chosen;
                            $weekSum += $this->costOf($menuCosts, $chosen, $averageSoup);
                            $placed++;
                            $history[] = ['date' => $day, 'id' => $chosen];
                        }
                    }
                }

                $position++;
            }
        }
    }

    /**
     * @param  list<array{date: CarbonImmutable, id: int}>  $history
     */
    private function lastSoup(array $history, CarbonImmutable $before): ?int
    {
        $found = null;

        foreach ($history as $entry) {
            if ($entry['date']->lt($before)) {
                $found = $entry['id'];
            }
        }

        return $found;
    }

    /**
     * @param  list<array{date: CarbonImmutable, id: int}>  $history
     * @return array<int, string>
     */
    private function lastSoupUsage(array $history, CarbonImmutable $before): array
    {
        $usage = [];

        foreach ($history as $entry) {
            if ($entry['date']->lt($before)) {
                $usage[$entry['id']] = $entry['date']->toDateString();
            }
        }

        return $usage;
    }

    /**
     * Data ultimei folosiri a fiecarei retete inainte de ziua data.
     *
     * @param  list<CarbonImmutable>  $dates
     */
    private function distanceToUsage(array $dates, CarbonImmutable $date): int
    {
        if ($dates === []) {
            return PHP_INT_MAX;
        }

        return (int) min(array_map(fn (CarbonImmutable $used): int => (int) $used->diffInDays($date, absolute: true), $dates));
    }

    /**
     * @param  Collection<int, Menu>  $menus
     * @return array<int, ?float>
     */
    private function portionCosts(Collection $menus): array
    {
        return $menus->map(function (Menu $menu): ?float {
            $probe = new DailyMeal(['estimated_people' => self::PROBE_PORTIONS]);

            if ($menu->type === 'soup') {
                $probe->setRelation('soupMenu', $menu);
            } else {
                $probe->setRelation('menu', $menu);
            }

            $probe->setRelation('dessertMenu', null);
            $result = $this->costCalculator->calculateFresh($probe);

            if ($result['total_cost'] <= 0 && $result['has_missing_prices']) {
                return null;
            }

            return round($result['total_cost'] / self::PROBE_PORTIONS, 2);
        })->all();
    }

    /** @param array<int, ?float> $costs */
    private function average(array $costs): float
    {
        $known = array_filter($costs, fn (?float $cost): bool => $cost !== null);

        return $known === [] ? 0.0 : array_sum($known) / count($known);
    }

    /** @param array<int, ?float> $costs */
    private function costOf(array $costs, ?int $menuId, float $fallback): float
    {
        if ($menuId === null) {
            return 0.0;
        }

        return $costs[$menuId] ?? $fallback;
    }

    /** @return list<string> */
    private function ingredientNames(?Menu $menu): array
    {
        if ($menu === null) {
            return [];
        }

        return collect($menu->ingredients ?? [])
            ->pluck('name')
            ->filter()
            ->map(fn ($name): string => mb_strtolower(trim((string) $name)))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{people: int, teams: int, contributions: int, transactions: int}>
     */
    private function childCounts(): array
    {
        $counts = [];

        $ensure = function (int $mealId) use (&$counts): array {
            return $counts[$mealId] ??= ['people' => 0, 'teams' => 0, 'contributions' => 0, 'transactions' => 0];
        };

        foreach (Volunteer::query()->selectRaw('daily_meal_id, count(*) as aggregate')->groupBy('daily_meal_id')->pluck('aggregate', 'daily_meal_id') as $mealId => $count) {
            $ensure((int) $mealId);
            $counts[(int) $mealId]['people'] = (int) $count;
        }

        foreach (WorkTeam::query()->selectRaw('daily_meal_id, count(*) as aggregate')->groupBy('daily_meal_id')->pluck('aggregate', 'daily_meal_id') as $mealId => $count) {
            $ensure((int) $mealId);
            $counts[(int) $mealId]['teams'] = (int) $count;
        }

        foreach (FoodFundTransaction::query()->whereNotNull('daily_meal_id')->selectRaw('daily_meal_id, count(*) as aggregate')->groupBy('daily_meal_id')->pluck('aggregate', 'daily_meal_id') as $mealId => $count) {
            $ensure((int) $mealId);
            $counts[(int) $mealId]['transactions'] = (int) $count;
        }

        foreach (FoodFundTransaction::query()->whereNotNull('daily_meal_id')->whereNotNull('volunteer_contribution_status')->selectRaw('daily_meal_id, count(*) as aggregate')->groupBy('daily_meal_id')->pluck('aggregate', 'daily_meal_id') as $mealId => $count) {
            $ensure((int) $mealId);
            $counts[(int) $mealId]['contributions'] = (int) $count;
        }

        return $counts;
    }

    /**
     * @param  array<int, array{people: int, teams: int, contributions: int, transactions: int}>  $childCounts
     */
    private function isProtected(DailyMeal $meal, array $childCounts): bool
    {
        $counts = $childCounts[$meal->id] ?? null;

        return $meal->confirmed_people !== null
            || (bool) $meal->is_cooked
            || $meal->actual_cost !== null
            || ($counts['people'] ?? 0) > 0
            || ($counts['teams'] ?? 0) > 0
            || ($counts['transactions'] ?? 0) > 0;
    }

    /**
     * @param  Collection<int, DailyMeal>  $meals
     * @param  Collection<int, string>  $menuNames
     * @return list<array<string, mixed>>
     */
    private function deletionPreview(Collection $meals, Collection $menuNames): array
    {
        return $meals
            ->groupBy('week_id')
            ->map(function (Collection $weekMeals) use ($menuNames): array {
                $sorted = $weekMeals->sortBy(fn (DailyMeal $meal): string => $meal->meal_date->toDateString())->values();

                return [
                    'week_number' => $sorted->first()->week?->week_number,
                    'start' => $sorted->first()->meal_date->toDateString(),
                    'end' => $sorted->last()->meal_date->toDateString(),
                    'days' => $sorted->map(fn (DailyMeal $meal): array => [
                        'date' => $meal->meal_date->toDateString(),
                        'main' => $meal->menu_id !== null ? ($menuNames[$meal->menu_id] ?? null) : null,
                        'soup' => $meal->soup_menu_id !== null ? ($menuNames[$meal->soup_menu_id] ?? null) : null,
                    ])->all(),
                ];
            })
            ->sortBy('start')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, DailyMeal>  $meals
     * @param  array<int, array{people: int, teams: int, contributions: int, transactions: int}>  $childCounts
     * @param  Collection<int, string>  $menuNames
     * @return list<array<string, mixed>>
     */
    private function protectedPreview(Collection $meals, array $childCounts, Collection $menuNames, bool $willDelete): array
    {
        return $meals
            ->filter(fn (DailyMeal $meal): bool => $this->isProtected($meal, $childCounts))
            ->sortBy(fn (DailyMeal $meal): string => $meal->meal_date->toDateString())
            ->map(function (DailyMeal $meal) use ($childCounts, $menuNames, $willDelete): array {
                $counts = $childCounts[$meal->id] ?? ['people' => 0, 'teams' => 0, 'contributions' => 0, 'transactions' => 0];

                return [
                    'date' => $meal->meal_date->toDateString(),
                    'week_number' => $meal->week?->week_number,
                    'main' => $meal->menu_id !== null ? ($menuNames[$meal->menu_id] ?? null) : null,
                    'people' => $counts['people'],
                    'teams' => $counts['teams'],
                    'contributions' => $counts['contributions'],
                    'transactions' => $counts['transactions'],
                    'will_delete' => $willDelete,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array{error: ?string, preview: array<string, mixed>, write: ?array<string, mixed>}  $plan
     * @return array<string, int>
     */
    private function writePlan(array $plan): array
    {
        $write = $plan['write'] ?? ['deletion_ids' => [], 'weeks' => [], 'days' => [], 'counts' => []];
        $originalWeekIds = Week::query()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        foreach ($write['deletion_ids'] as $mealId) {
            $meal = DailyMeal::query()->find($mealId);

            if ($meal !== null) {
                $this->deleteMeal($meal);
            }
        }

        $weekMap = [];
        $nextNumber = (int) Week::query()->max('week_number') + 1;

        foreach ($write['weeks'] as $weekPlan) {
            if ($weekPlan['week_id'] !== null) {
                $week = Week::query()->find($weekPlan['week_id']);

                if ($week === null) {
                    continue;
                }

                $week->start_date = $weekPlan['start'];
                $week->saveQuietly();
            } else {
                $week = Week::create([
                    'week_number' => $nextNumber++,
                    'start_date' => $weekPlan['start'],
                    'include_saturday' => $weekPlan['include_saturday'],
                    'congregation_id' => $weekPlan['congregation_id'],
                ]);
            }

            $weekMap[$weekPlan['key']] = $week->id;
        }

        foreach ($write['days'] as $dayPlan) {
            $weekId = $weekMap[$dayPlan['plan_week_key']] ?? null;

            if ($weekId === null) {
                continue;
            }

            if ($dayPlan['status'] === 'new') {
                DailyMeal::create([
                    'meal_date' => $dayPlan['date'],
                    'week_id' => $weekId,
                    'congregation_id' => $dayPlan['congregation_id'],
                    'menu_id' => $dayPlan['main_id'],
                    'soup_menu_id' => $dayPlan['soup_id'],
                    'estimated_people' => 0,
                    'status' => 'draft',
                    'is_free_day' => $dayPlan['free'],
                ]);

                continue;
            }

            if ((int) $dayPlan['week_id'] !== (int) $weekId) {
                $meal = DailyMeal::query()->find($dayPlan['existing_id']);

                if ($meal !== null) {
                    $meal->week_id = $weekId;
                    $meal->saveQuietly();
                }
            }
        }

        $this->afterStructureApplied();

        Week::query()->doesntHave('dailyMeals')->get()->each(fn (Week $week) => $week->delete());

        $this->renumberWeeks();

        $remainingWeekIds = Week::query()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return [
            'created_weeks' => count($write['weeks']) - count(array_filter($write['weeks'], fn (array $week): bool => $week['week_id'] !== null)),
            'created_days' => collect($write['days'])->where('status', 'new')->count(),
            'deleted_weeks' => count(array_diff($originalWeekIds, $remainingWeekIds)),
            'deleted_days' => count($write['deletion_ids']),
            'kept_days' => collect($write['days'])->where('status', '!=', 'new')->count(),
        ];
    }

    /**
     * Punct de extensie pentru testarea tranzactiei: orice exceptie aici anuleaza tot.
     */
    protected function afterStructureApplied(): void {}

    private function deleteMeal(DailyMeal $meal): void
    {
        $meal->supplyPlan?->consumptions()->delete();
        $meal->supplyPlan?->delete();
        $meal->volunteers()->delete();
        $meal->workTeams()->delete();
        FoodFundTransaction::query()->where('daily_meal_id', $meal->id)->delete();
        $meal->delete();
    }

    private function renumberWeeks(): void
    {
        DB::table('weeks')->update(['week_number' => DB::raw('week_number + 10000')]);

        $weeks = Week::query()->orderBy('start_date')->orderBy('id')->get();

        foreach ($weeks as $index => $week) {
            $week->week_number = $index + 1;
            $week->saveQuietly();
        }
    }

    /**
     * @return array{error: string, preview: array<string, mixed>, write: null}
     */
    private function errorPlan(string $error): array
    {
        return [
            'error' => $error,
            'preview' => [
                'error' => $error,
                'mode' => null,
                'weeks' => [],
                'deleted' => [],
                'protected' => [],
                'delete_protected' => false,
                'spread' => 0.0,
                'has_missing_prices' => false,
                'created_weeks' => 0,
                'created_days' => 0,
                'deleted_weeks' => 0,
                'deleted_days' => 0,
                'kept_days' => 0,
            ],
            'write' => null,
        ];
    }
}
