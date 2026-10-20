<?php

namespace App\Console\Commands;

use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Week;
use App\Services\MealRotation;
use App\Services\MenuSchedulingValidator;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class FillMenus extends Command
{
    protected $signature = 'oms:fill-menus {--dry-run : Afiseaza ce s-ar completa, fara a salva}';

    protected $description = 'Completeaza retetele lipsa din zilele inca in format draft, dupa ordinea meselor si regula celor doua ciorbe';

    public function handle(MealRotation $rotation): int
    {
        $meals = DailyMeal::query()
            ->orderBy('meal_date')
            ->orderBy('id')
            ->get()
            ->values();

        if ($meals->isEmpty()) {
            $this->info('Nu exista zile de completat.');

            return self::SUCCESS;
        }

        $mains = $this->activeRecipes('main');
        $soups = $this->activeRecipes('soup');

        if ($mains->isEmpty() || $soups->isEmpty()) {
            $this->error('Catalogul nu are retete active pentru fel principal si ciorba.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $result = $this->fillMeals($rotation, $meals, $mains, $soups, $dryRun);

        $this->table(['Data', 'Zi', 'Fel principal', 'Ciorba'], $result['sequence']);

        $this->info(sprintf(
            'Fel principal completat: %d; ciorba completata: %d; zile libere sarite: %d.',
            $result['main_filled'],
            $result['soup_filled'],
            $result['free_days'],
        ));

        foreach ($this->issues($meals, Week::query()->pluck('week_number', 'id'), $rotation) as $issue) {
            $this->warn($issue);
        }

        if ($dryRun) {
            $this->info('Mod de testare (--dry-run): nu s-a modificat nimic.');

            return self::SUCCESS;
        }

        $this->info('Retetele lipsa au fost completate.');

        return self::SUCCESS;
    }

    /**
     * Sugereaza meniul pentru zilele goale dintr-o singura saptamana. Folosit de butonul
     * "Sugereaza meniul" din calendar; completeaza doar zilele fara fel principal.
     */
    public function fillWeek(Week $week): int
    {
        $rotation = app(MealRotation::class);

        $mains = $this->activeRecipes('main');
        $soups = $this->activeRecipes('soup');

        if ($mains->isEmpty() || $soups->isEmpty()) {
            return 0;
        }

        $meals = DailyMeal::query()
            ->orderBy('meal_date')
            ->orderBy('id')
            ->get()
            ->values();

        $weekMeals = $meals->where('week_id', $week->id)->values();

        if ($weekMeals->isEmpty()) {
            return 0;
        }

        $result = $this->fillMeals($rotation, $meals, $mains, $soups, false, $week->id);

        return $result['main_filled'] + $result['soup_filled'];
    }

    /**
     * @param  Collection<int, DailyMeal>  $meals
     * @param  Collection<int, Menu>  $mains
     * @param  Collection<int, Menu>  $soups
     * @return array{sequence: array<int, array<int, string|null>>, main_filled: int, soup_filled: int, free_days: int}
     */
    private function fillMeals(
        MealRotation $rotation,
        Collection $meals,
        Collection $mains,
        Collection $soups,
        bool $dryRun,
        ?int $onlyWeekId = null,
    ): array {
        $weeks = Week::query()->pluck('week_number', 'id');
        $mainOrder = $rotation->mainOrder($meals);
        $usedMainDates = $this->usedMainDates($meals);

        $sequence = [];
        $mainFilled = 0;
        $soupFilled = 0;
        $freeDays = 0;

        foreach ($meals as $meal) {
            if ($meal->is_free_day) {
                $freeDays++;

                if ($onlyWeekId === null || $meal->week_id === $onlyWeekId) {
                    $sequence[] = [$meal->meal_date->toDateString(), $meal->meal_date->translatedFormat('D'), 'ZI LIBERA', '-'];
                }

                continue;
            }

            $soupsInWeek = $this->soupsInWeek($meals, $meal->week_id)
                ->map(fn (DailyMeal $weekly): ?int => $weekly->soup_menu_id)
                ->filter()
                ->values();

            $positionInWeek = $rotation->positionInWeek($meals, $meal);

            [$mainChanged, $soupChanged] = $this->fillMeal(
                $meal,
                $rotation,
                $mains,
                $soups,
                $mainOrder[$meal->id] ?? 0,
                (int) ($weeks[$meal->week_id] ?? 0),
                $positionInWeek,
                $soupsInWeek,
                $usedMainDates,
            );

            $mainFilled += $mainChanged ? 1 : 0;
            $soupFilled += $soupChanged ? 1 : 0;

            $mainName = $meal->menu_id === null
                ? '-'
                : $mains->firstWhere('id', $meal->menu_id)?->name;

            $soupName = $meal->soup_menu_id === null
                ? '-'
                : $soups->firstWhere('id', $meal->soup_menu_id)?->name;

            if ($onlyWeekId === null || $meal->week_id === $onlyWeekId) {
                $sequence[] = [$meal->meal_date->toDateString(), $meal->meal_date->translatedFormat('D'), $mainName ?? '-', $soupName ?? '-'];
            }

            if (! $dryRun && ($mainChanged || $soupChanged)) {
                $meal->save();
            }
        }

        return [
            'sequence' => $sequence,
            'main_filled' => $mainFilled,
            'soup_filled' => $soupFilled,
            'free_days' => $freeDays,
        ];
    }

    /**
     * @return Collection<int, Menu>
     */
    private function activeRecipes(string $type): Collection
    {
        return Menu::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->values();
    }

    /**
     * Mesele fara zi libera din aceeasi saptamana.
     *
     * @param  Collection<int, DailyMeal>  $meals
     * @return Collection<int, DailyMeal>
     */
    private function soupsInWeek(Collection $meals, ?int $weekId): Collection
    {
        return $meals->where('week_id', $weekId);
    }

    /**
     * @param  Collection<int, Menu>  $mains
     * @param  Collection<int, Menu>  $soups
     * @param  array<int, array<int, CarbonImmutable>>  $usedMainDates
     * @param  Collection<int, int>  $soupsInWeek
     * @return array{0: bool, 1: bool}
     */
    private function fillMeal(
        DailyMeal $meal,
        MealRotation $rotation,
        Collection $mains,
        Collection $soups,
        int $order,
        int $weekNumber,
        ?int $positionInWeek,
        Collection $soupsInWeek,
        array &$usedMainDates,
    ): array {
        $mainChanged = false;
        $soupChanged = false;

        if ($meal->status === 'draft' && $meal->menu_id === null) {
            $main = $this->pickMain($rotation, $mains, $order, $meal->meal_date, $usedMainDates);

            if ($main !== null) {
                $meal->menu_id = $main->id;
                $usedMainDates[$main->id][] = $meal->meal_date;
                $mainChanged = true;
            }
        }

        if ($meal->status === 'draft'
            && $meal->soup_menu_id === null
            && $positionInWeek !== null
            && $rotation->hostsSoup($positionInWeek)) {
            $soupIndex = $rotation->soupRecipeIndex($soups->count(), $weekNumber, $positionInWeek);
            $soup = $soupIndex === null ? null : $soups[$soupIndex];

            if ($soup !== null && ! $soupsInWeek->contains($soup->id)) {
                $meal->soup_menu_id = $soup->id;
                $soupChanged = true;
            }
        }

        return [$mainChanged, $soupChanged];
    }

    /**
     * Alege urmatorul fel principal din ciclu care nu a fost gatit in ultimele 10 zile
     * calendaristice, tinand cont si de mesele completate inainte. Daca toate retetele
     * din ciclu sunt prea recente, revine la candidatul de rotatie pentru a nu lasa ziua goala.
     *
     * @param  Collection<int, Menu>  $mains
     * @param  array<int, array<int, CarbonImmutable>>  $usedMainDates
     */
    private function pickMain(
        MealRotation $rotation,
        Collection $mains,
        int $order,
        CarbonInterface $date,
        array $usedMainDates,
    ): ?Menu {
        if ($mains->isEmpty()) {
            return null;
        }

        $fallback = $rotation->mainRecipe($mains, $order);

        for ($offset = 0; $offset < $mains->count(); $offset++) {
            $candidate = $mains[($order + $offset) % $mains->count()];

            if ($this->respectsMinimumDistance($usedMainDates[$candidate->id] ?? [], $date)) {
                return $candidate;
            }
        }

        return $fallback;
    }

    /**
     * @param  array<int, CarbonImmutable>  $usedDates
     */
    private function respectsMinimumDistance(array $usedDates, CarbonInterface $date): bool
    {
        foreach ($usedDates as $usedDate) {
            if ((int) $usedDate->diffInDays($date, absolute: true) < MenuSchedulingValidator::MINIMUM_DAYS_BETWEEN_MAIN_RECIPES) {
                return false;
            }
        }

        return true;
    }

    /**
     * Datele (calendaristice) in care a fost gatita fiecare reteta, din mesele deja completate.
     *
     * @param  Collection<int, DailyMeal>  $meals
     * @return array<int, array<int, CarbonImmutable>>
     */
    private function usedMainDates(Collection $meals): array
    {
        $used = [];

        foreach ($meals as $meal) {
            if ($meal->is_free_day || $meal->menu_id === null) {
                continue;
            }

            $used[$meal->menu_id][] = $meal->meal_date;
        }

        return $used;
    }

    /**
     * @param  Collection<int, DailyMeal>  $meals
     * @param  Collection<int, int>  $weeks
     * @return array<int, string>
     */
    private function issues(Collection $meals, Collection $weeks, MealRotation $rotation): array
    {
        $issues = [];

        foreach ($meals->groupBy('week_id') as $weekId => $weekMeals) {
            $programmed = $weekMeals
                ->reject(fn (DailyMeal $meal): bool => (bool) $meal->is_free_day)
                ->sortBy(fn (DailyMeal $meal) => $meal->meal_date->toDateString())
                ->values();

            $weekNumber = (int) ($weeks[$weekId] ?? 0);
            $soups = $programmed->whereNotNull('soup_menu_id');

            if ($soups->count() > MenuSchedulingValidator::SOUP_LIMIT_PER_WEEK) {
                $issues[] = 'Saptamana '.$weekNumber.' are '.$soups->count().' ciorbe (maximum '.MenuSchedulingValidator::SOUP_LIMIT_PER_WEEK.').';
            }

            if ($soups->count() !== $soups->pluck('soup_menu_id')->unique()->count()) {
                $issues[] = 'Saptamana '.$weekNumber.' are aceeasi ciorba de doua ori; se recomanda doua ciorbe diferite.';
            }

            foreach ($programmed as $index => $meal) {
                if ($meal->soup_menu_id !== null && ! $rotation->hostsSoup($index)) {
                    $issues[] = 'Ciorba din '.$meal->meal_date->toDateString().' nu cade pe a 2-a sau a 4-a masa a saptamanii.';
                }
            }
        }

        $mainDates = [];

        foreach ($meals->reject(fn (DailyMeal $meal): bool => (bool) $meal->is_free_day)->whereNotNull('menu_id') as $meal) {
            $mainDates[$meal->menu_id][] = $meal->meal_date;
        }

        foreach ($mainDates as $dates) {
            sort($dates);

            for ($index = 1; $index < count($dates); $index++) {
                $distance = (int) $dates[$index - 1]->diffInDays($dates[$index], absolute: true);

                if ($distance < MenuSchedulingValidator::MINIMUM_DAYS_BETWEEN_MAIN_RECIPES) {
                    $issues[] = sprintf(
                        'Felul principal se repeta la doar %d zile (%s -> %s); se recomanda minimum %d zile.',
                        $distance,
                        $dates[$index - 1]->toDateString(),
                        $dates[$index]->toDateString(),
                        MenuSchedulingValidator::MINIMUM_DAYS_BETWEEN_MAIN_RECIPES,
                    );

                    break;
                }
            }
        }

        return $issues;
    }
}
