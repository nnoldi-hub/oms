<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Menu;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Planifica meniul pe o perioada (mai multe saptamani deodata):
 *
 * - felul principal nu se repeta mai devreme de MINIMUM_DAYS_BETWEEN_MAIN_RECIPES zile;
 *   dintre retetele folosite cel mai demult se alege cea care tine costul saptamanii
 *   aproape de media catalogului, ca saptamanile sa fie echilibrate ca pret;
 * - ciorba se pune pe a 2-a si a 4-a masa din saptamana, doua ciorbe diferite, alta decat
 *   ciorba anterioara si, daca se poate, fara ingredient dominant comun cu felul principal;
 * - zilele trecute, cele gatite, cele publicate si zilele libere nu se modifica.
 */
class PeriodMenuPlanner
{
    private const PROBE_PORTIONS = 100;

    public function __construct(private DailyMealCostCalculator $costCalculator) {}

    /**
     * @return array{
     *     error: ?string,
     *     changed: int,
     *     weeks: list<array{week_number: int, start: string, end: string, days: list<array{date: string, main: ?string, soup: ?string, changed: bool}>, cost_per_portion: float}>,
     *     spread: float,
     *     has_missing_prices: bool
     * }
     */
    public function plan(CarbonInterface $from, CarbonInterface $to, bool $replaceExisting, bool $apply): array
    {
        $mains = Menu::query()->where('type', 'main')->where('is_active', true)->orderBy('id')->get()->keyBy('id');
        $soups = Menu::query()->where('type', 'soup')->where('is_active', true)->orderBy('id')->get()->keyBy('id');

        if ($mains->isEmpty()) {
            return $this->emptyResult('Catalogul nu are feluri principale active.');
        }

        $from = CarbonImmutable::parse($from)->startOfDay();
        $to = CarbonImmutable::parse($to)->startOfDay();
        $today = CarbonImmutable::today();

        $allMeals = DailyMeal::query()
            ->with('week')
            ->orderBy('meal_date')
            ->orderBy('id')
            ->get();

        $inRange = $allMeals
            ->filter(fn (DailyMeal $meal): bool => ! $meal->is_free_day
                && $meal->meal_date->betweenIncluded($from, $to))
            ->values();

        if ($inRange->isEmpty()) {
            return $this->emptyResult('Nu exista zile de masa programate in perioada aleasa.');
        }

        $editable = fn (DailyMeal $meal): bool => ! $meal->is_cooked
            && $meal->status === 'draft'
            && $meal->meal_date->greaterThanOrEqualTo($today);

        $original = $allMeals->mapWithKeys(fn (DailyMeal $meal): array => [
            $meal->id => [$this->id($meal->menu_id), $this->id($meal->soup_menu_id)],
        ])->all();

        // In modul "inlocuieste", zilele modificabile pornesc de la zero, ca sa nu conteze la repetari.
        if ($replaceExisting) {
            foreach ($inRange->filter($editable) as $meal) {
                $meal->menu_id = null;
                $meal->soup_menu_id = null;
            }
        }

        $mainCosts = $this->portionCosts($mains);
        $soupCosts = $this->portionCosts($soups);
        $averageMain = $this->average($mainCosts);
        $averageSoup = $this->average($soupCosts);

        $usage = $this->mainUsage($allMeals);

        foreach ($inRange->groupBy('week_id') as $weekMeals) {
            $this->planMains($weekMeals->values(), $editable, $mains, $mainCosts, $averageMain, $usage);
        }

        foreach ($inRange->groupBy('week_id') as $weekId => $weekMeals) {
            $this->planSoups($allMeals, $weekMeals->values(), (int) $weekId, $editable, $replaceExisting, $mains, $soups, $soupCosts, $averageSoup);
        }

        $changedMeals = $inRange->filter(fn (DailyMeal $meal): bool => $original[$meal->id] !== [$this->id($meal->menu_id), $this->id($meal->soup_menu_id)]);
        $planned = $inRange->mapWithKeys(fn (DailyMeal $meal): array => [$meal->id => [$this->id($meal->menu_id), $this->id($meal->soup_menu_id)]])->all();

        if ($apply) {
            DB::transaction(function () use ($changedMeals): void {
                foreach ($changedMeals as $meal) {
                    $meal->save();
                }
            });
        } else {
            // Previzualizare: modelele raman nesalvate si revin la valorile initiale.
            foreach ($allMeals as $meal) {
                [$meal->menu_id, $meal->soup_menu_id] = $original[$meal->id];
            }
        }

        $weeks = $this->summary($inRange, $changedMeals->pluck('id')->all(), $planned, $mains, $soups, $mainCosts, $soupCosts);
        $weekCosts = array_column($weeks, 'cost_per_portion');

        return [
            'error' => null,
            'changed' => $changedMeals->count(),
            'weeks' => $weeks,
            'spread' => $weekCosts === [] ? 0.0 : round(max($weekCosts) - min($weekCosts), 2),
            'has_missing_prices' => in_array(null, $mainCosts, true) || in_array(null, $soupCosts, true),
        ];
    }

    /**
     * @param  Collection<int, DailyMeal>  $weekMeals
     * @param  Collection<int, Menu>  $mains
     * @param  array<int, ?float>  $mainCosts
     * @param  array<int, list<CarbonImmutable>>  $usage
     */
    private function planMains(Collection $weekMeals, callable $editable, Collection $mains, array $mainCosts, float $averageMain, array &$usage): void
    {
        $weekSum = 0.0;

        foreach ($weekMeals->sortBy(fn (DailyMeal $meal): string => $meal->meal_date->toDateString())->values() as $index => $meal) {
            if ($meal->menu_id !== null || ! $editable($meal)) {
                $weekSum += $this->costOf($mainCosts, $meal->menu_id, $averageMain);

                continue;
            }

            $date = CarbonImmutable::parse($meal->meal_date);
            $ranked = $mains->keys()
                ->map(fn (int $id): array => ['id' => $id, 'distance' => $this->distanceToUsage($usage[$id] ?? [], $date)])
                ->sortBy([['distance', 'desc'], ['id', 'asc']])
                ->values();

            $allowed = $ranked->filter(fn (array $candidate): bool => $candidate['distance'] >= MenuSchedulingValidator::MINIMUM_DAYS_BETWEEN_MAIN_RECIPES)->values();
            $pool = $allowed->isNotEmpty() ? $allowed : $ranked->take(1);

            // Dintre retetele folosite cel mai demult, cea care tine saptamana cel mai aproape de medie.
            $shortlist = $pool->take(max(3, (int) ceil($pool->count() / 2)));
            $target = $averageMain * ($index + 1);
            $chosen = $shortlist
                ->sortBy(fn (array $candidate): float => abs($weekSum + $this->costOf($mainCosts, $candidate['id'], $averageMain) - $target))
                ->first();

            $meal->menu_id = $chosen['id'];
            $usage[$chosen['id']][] = $date;
            $weekSum += $this->costOf($mainCosts, $chosen['id'], $averageMain);
        }
    }

    /**
     * @param  Collection<int, DailyMeal>  $allMeals
     * @param  Collection<int, DailyMeal>  $weekMeals  mesele saptamanii din perioada
     * @param  Collection<int, Menu>  $mains
     * @param  Collection<int, Menu>  $soups
     * @param  array<int, ?float>  $soupCosts
     */
    private function planSoups(Collection $allMeals, Collection $weekMeals, int $weekId, callable $editable, bool $replaceExisting, Collection $mains, Collection $soups, array $soupCosts, float $averageSoup): void
    {
        if ($soups->isEmpty()) {
            return;
        }

        // Pozitia in saptamana se calculeaza pe toate mesele saptamanii, nu doar pe cele din perioada.
        $positions = $allMeals
            ->where('week_id', $weekId)
            ->reject(fn (DailyMeal $meal): bool => (bool) $meal->is_free_day)
            ->sortBy(fn (DailyMeal $meal): string => $meal->meal_date->toDateString())
            ->values()
            ->mapWithKeys(fn (DailyMeal $meal, int $position): array => [$meal->id => $position])
            ->all();

        $weekSoupSum = 0.0;
        $placed = 0;

        foreach ($weekMeals->sortBy(fn (DailyMeal $meal): string => $meal->meal_date->toDateString()) as $meal) {
            $hostsSoup = in_array($positions[$meal->id] ?? -1, MealRotation::SOUP_SLOTS_PER_WEEK, true);

            if (! $editable($meal) || $meal->soup_menu_id !== null) {
                if ($meal->soup_menu_id !== null) {
                    $weekSoupSum += $this->costOf($soupCosts, $meal->soup_menu_id, $averageSoup);
                    $placed++;
                }

                continue;
            }

            $weekSoups = $allMeals->where('week_id', $weekId)->pluck('soup_menu_id')->filter()->map(fn ($id): int => (int) $id)->values()->all();

            if (! $hostsSoup || count($weekSoups) >= MenuSchedulingValidator::SOUP_LIMIT_PER_WEEK) {
                continue;
            }

            $previousSoup = $allMeals
                ->filter(fn (DailyMeal $other): bool => $other->soup_menu_id !== null && $other->meal_date->lt($meal->meal_date))
                ->sortBy(fn (DailyMeal $other): string => $other->meal_date->toDateString())
                ->last()?->soup_menu_id;
            $previousSoup = $this->id($previousSoup);

            $mainIngredients = $this->ingredientNames($mains->get($meal->menu_id));

            $candidates = $soups->keys()
                ->reject(fn (int $id): bool => in_array($id, $weekSoups, true) || $id === $previousSoup)
                ->values();

            if ($candidates->isEmpty()) {
                $candidates = $soups->keys()->reject(fn (int $id): bool => in_array($id, $weekSoups, true))->values();
            }

            if ($candidates->isEmpty()) {
                continue;
            }

            $withoutSharedIngredient = $candidates
                ->filter(fn (int $id): bool => array_intersect($mainIngredients, $this->ingredientNames($soups->get($id))) === [])
                ->values();

            $pool = $withoutSharedIngredient->isNotEmpty() ? $withoutSharedIngredient : $candidates;
            $lastUsed = $this->lastSoupUsage($allMeals, $meal);

            $shortlist = $pool
                ->sortBy([fn (int $a, int $b): int => ($lastUsed[$a] ?? '') <=> ($lastUsed[$b] ?? ''), fn (int $a, int $b): int => $a <=> $b])
                ->take(2);

            $target = $averageSoup * ($placed + 1);
            $chosen = $shortlist
                ->sortBy(fn (int $id): float => abs($weekSoupSum + $this->costOf($soupCosts, $id, $averageSoup) - $target))
                ->first();

            $meal->soup_menu_id = $chosen;
            $weekSoupSum += $this->costOf($soupCosts, $chosen, $averageSoup);
            $placed++;
        }
    }

    /**
     * Data ultimei folosiri a fiecarei ciorbe inainte de masa data.
     *
     * @param  Collection<int, DailyMeal>  $allMeals
     * @return array<int, string>
     */
    private function lastSoupUsage(Collection $allMeals, DailyMeal $before): array
    {
        return $allMeals
            ->filter(fn (DailyMeal $meal): bool => $meal->soup_menu_id !== null && $meal->meal_date->lt($before->meal_date))
            ->mapWithKeys(fn (DailyMeal $meal): array => [$meal->soup_menu_id => $meal->meal_date->toDateString()])
            ->all();
    }

    /**
     * @param  Collection<int, DailyMeal>  $allMeals
     * @return array<int, list<CarbonImmutable>>
     */
    private function mainUsage(Collection $allMeals): array
    {
        $usage = [];

        foreach ($allMeals as $meal) {
            if (! $meal->is_free_day && $meal->menu_id !== null) {
                $usage[$meal->menu_id][] = CarbonImmutable::parse($meal->meal_date);
            }
        }

        return $usage;
    }

    /** @param list<CarbonImmutable> $dates */
    private function distanceToUsage(array $dates, CarbonImmutable $date): int
    {
        if ($dates === []) {
            return PHP_INT_MAX;
        }

        return (int) min(array_map(fn (CarbonImmutable $used): int => (int) $used->diffInDays($date, absolute: true), $dates));
    }

    /**
     * Costul per portie al fiecarei retete (null daca reteta nu are preturi).
     *
     * @param  Collection<int, Menu>  $menus
     * @return array<int, ?float>
     */
    private function portionCosts(Collection $menus): array
    {
        return $menus->map(function (Menu $menu): ?float {
            $probe = new DailyMeal(['estimated_people' => self::PROBE_PORTIONS]);
            $probe->setRelation('menu', $menu->type === 'main' ? $menu : null);
            $probe->setRelation('soupMenu', $menu->type === 'soup' ? $menu : null);
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
     * @param  Collection<int, DailyMeal>  $inRange
     * @param  list<int>  $changedIds
     * @param  array<int, array{0: ?int, 1: ?int}>  $planned  meal_id => [fel principal, ciorba] dupa planificare
     * @param  Collection<int, Menu>  $mains
     * @param  Collection<int, Menu>  $soups
     * @param  array<int, ?float>  $mainCosts
     * @param  array<int, ?float>  $soupCosts
     * @return list<array{week_number: int, start: string, end: string, days: list<array{date: string, main: ?string, soup: ?string, changed: bool}>, cost_per_portion: float}>
     */
    private function summary(Collection $inRange, array $changedIds, array $planned, Collection $mains, Collection $soups, array $mainCosts, array $soupCosts): array
    {
        $averageMain = $this->average($mainCosts);
        $averageSoup = $this->average($soupCosts);

        return $inRange
            ->groupBy('week_id')
            ->map(function (Collection $weekMeals) use ($changedIds, $planned, $mains, $soups, $mainCosts, $soupCosts, $averageMain, $averageSoup): array {
                $sorted = $weekMeals->sortBy(fn (DailyMeal $meal): string => $meal->meal_date->toDateString())->values();
                $total = 0.0;

                $days = $sorted->map(function (DailyMeal $meal) use ($changedIds, $planned, $mains, $soups, $mainCosts, $soupCosts, $averageMain, $averageSoup, &$total): array {
                    $changed = in_array($meal->id, $changedIds, true);
                    [$mainId, $soupId] = $planned[$meal->id];
                    $total += $this->costOf($mainCosts, $mainId, $averageMain) + $this->costOf($soupCosts, $soupId, $averageSoup);

                    return [
                        'date' => $meal->meal_date->toDateString(),
                        'main' => $mainId ? ($mains->get($mainId)?->name ?? Menu::query()->whereKey($mainId)->value('name')) : null,
                        'soup' => $soupId ? ($soups->get($soupId)?->name ?? Menu::query()->whereKey($soupId)->value('name')) : null,
                        'changed' => $changed,
                    ];
                })->all();

                return [
                    'week_number' => (int) $sorted->first()->week?->week_number,
                    'start' => $sorted->first()->meal_date->toDateString(),
                    'end' => $sorted->last()->meal_date->toDateString(),
                    'days' => $days,
                    'cost_per_portion' => round($total / max(1, $sorted->count()), 2),
                ];
            })
            ->sortBy('start')
            ->values()
            ->all();
    }

    private function id(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    /** @return array{error: string, changed: int, weeks: list<never>, spread: float, has_missing_prices: bool} */
    private function emptyResult(string $error): array
    {
        return ['error' => $error, 'changed' => 0, 'weeks' => [], 'spread' => 0.0, 'has_missing_prices' => false];
    }
}
