<?php

namespace App\Console\Commands;

use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Week;
use App\Services\MenuSchedulingValidator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class FillMenus extends Command
{
    protected $signature = 'oms:fill-menus {--dry-run : Afiseaza ce s-ar completa, fara a salva}';

    protected $description = 'Completeaza retetele lipsa din zilele inca in format draft, respectand rotatia si regula celor doua ciorbe';

    public function handle(): int
    {
        $meals = DailyMeal::query()->orderBy('meal_date')->get()->values();

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

        $positions = [];
        foreach ($meals as $index => $meal) {
            $positions[CarbonImmutable::parse($meal->meal_date)->toDateString()] = $index;
        }

        $mainDates = $this->recipeDates($meals, 'menu_id');
        $soupsByWeek = $this->soupsByWeek($meals);

        $changed = [];
        $rows = [];
        $mainCount = 0;
        $soupCount = 0;
        $previousSoupMenu = null;

        foreach ($meals as $meal) {
            if ($meal->status !== 'draft') {
                continue;
            }

            $date = CarbonImmutable::parse($meal->meal_date);

            if ($meal->menu_id === null && $meal->week_id !== null) {
                $menu = $this->pickMain($mains, $positions[$date->toDateString()], $mainDates, $date);

                if ($menu !== null) {
                    $meal->menu_id = $menu->id;
                    $mainDates[$menu->id][] = $date->toDateString();
                    $changed[] = $meal;
                    $rows[] = [$date->toDateString(), 'Fel principal', $menu->name];
                    $mainCount++;
                }
            }

            if ($this->canFillSoup($meal, $date, $soupsByWeek)) {
                $soup = $this->pickSoup($soups, $soupsByWeek[$meal->week_id] ?? [], $previousSoupMenu);

                if ($soup !== null) {
                    $meal->soup_menu_id = $soup->id;
                    $soupsByWeek[$meal->week_id][$soup->id] = true;
                    $changed[] = $meal;
                    $rows[] = [$date->toDateString(), 'Ciorba', $soup->name];
                    $soupCount++;
                }
            }

            if ($meal->soup_menu_id !== null) {
                $previousSoupMenu = $meal->soup_menu_id;
            }
        }

        if ($rows !== []) {
            $this->table(['Data', 'Camp', 'Reteta'], $rows);
        }

        $this->info(sprintf(
            'Fel principal completat: %d; ciorba completata: %d.',
            $mainCount,
            $soupCount,
        ));

        foreach ($this->issues($meals) as $issue) {
            $this->warn($issue);
        }

        if ($this->option('dry-run')) {
            $this->info('Mod de testare (--dry-run): nu s-a modificat nimic.');

            return self::SUCCESS;
        }

        foreach ($changed as $meal) {
            $meal->save();
        }

        return self::SUCCESS;
    }

    /** @return Collection<int, Menu> */
    private function activeRecipes(string $type): Collection
    {
        return Menu::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->values();
    }

    /** @return array<int, array<int, string>> */
    private function recipeDates(Collection $meals, string $column): array
    {
        $dates = [];

        foreach ($meals as $meal) {
            if ($meal->{$column} !== null) {
                $dates[$meal->{$column}][] = CarbonImmutable::parse($meal->meal_date)->toDateString();
            }
        }

        return $dates;
    }

    /** @return array<int, array<int, bool>> */
    private function soupsByWeek(Collection $meals): array
    {
        $soupsByWeek = [];

        foreach ($meals as $meal) {
            if ($meal->week_id !== null && $meal->soup_menu_id !== null) {
                $soupsByWeek[$meal->week_id][$meal->soup_menu_id] = true;
            }
        }

        return $soupsByWeek;
    }

    /**
     * @param Collection<int, Menu> $mains
     * @param array<int, array<int, string>> $mainDates
     */
    private function pickMain(Collection $mains, int $position, array $mainDates, CarbonImmutable $date): ?Menu
    {
        for ($offset = 0; $offset < $mains->count(); $offset++) {
            $candidate = $mains[($position + $offset) % $mains->count()];

            if ($this->respectsMinimumDistance($mainDates[$candidate->id] ?? [], $date)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param Collection<int, Menu> $soups
     * @param array<int, bool> $usedInWeek
     */
    private function pickSoup(Collection $soups, array $usedInWeek, ?int $previousSoupMenu): ?Menu
    {
        for ($offset = 0; $offset < $soups->count(); $offset++) {
            $candidate = $soups[$offset];

            if (isset($usedInWeek[$candidate->id]) || $candidate->id === $previousSoupMenu) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /** @param array<int, string> $usedDates */
    private function respectsMinimumDistance(array $usedDates, CarbonImmutable $date): bool
    {
        foreach ($usedDates as $usedDate) {
            $distance = (int) CarbonImmutable::parse($usedDate)->diffInDays($date, absolute: true);

            if ($distance < MenuSchedulingValidator::MINIMUM_DAYS_BETWEEN_MAIN_RECIPES) {
                return false;
            }
        }

        return true;
    }

    private function canFillSoup(DailyMeal $meal, CarbonImmutable $date, array $soupsByWeek): bool
    {
        if ($meal->soup_menu_id !== null || $meal->week_id === null) {
            return false;
        }

        if (! in_array((int) $date->isoWeekday(), MenuSchedulingValidator::SOUP_WEEKDAYS, true)) {
            return false;
        }

        return count($soupsByWeek[$meal->week_id] ?? []) < MenuSchedulingValidator::SOUP_LIMIT_PER_WEEK;
    }

    /** @return array<int, string> */
    private function issues(Collection $meals): array
    {
        $issues = [];

        foreach ($meals->groupBy('week_id') as $weekId => $weekMeals) {
            $soups = $weekMeals->whereNotNull('soup_menu_id');
            $weekNumber = Week::find($weekId)?->week_number ?? $weekId;

            if ($soups->count() > MenuSchedulingValidator::SOUP_LIMIT_PER_WEEK) {
                $issues[] = 'Saptamana '.$weekNumber.' are '.$soups->count().' ciorbe (maximum '.MenuSchedulingValidator::SOUP_LIMIT_PER_WEEK.').';
            }

            foreach ($soups as $soupedMeal) {
                $weekday = CarbonImmutable::parse($soupedMeal->meal_date)->isoWeekday();

                if (! in_array($weekday, MenuSchedulingValidator::SOUP_WEEKDAYS, true)) {
                    $issues[] = 'Ciorba din '.CarbonImmutable::parse($soupedMeal->meal_date)->toDateString().' cade intr-o zi nepermisa (ziua '.$weekday.').';
                }
            }
        }

        return $issues;
    }
}
