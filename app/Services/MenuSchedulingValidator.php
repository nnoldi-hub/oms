<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Menu;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class MenuSchedulingValidator
{
    /**
     * Campurile care schimba programarea propriu-zisa a retetelor.
     *
     * @var array<int, string>
     */
    public const SCHEDULE_FIELDS = [
        'meal_date',
        'week_id',
        'congregation_id',
        'menu_id',
        'soup_menu_id',
        'dessert_menu_id',
    ];

    public const MINIMUM_DAYS_BETWEEN_MAIN_RECIPES = 10;

    public const SOUP_LIMIT_PER_WEEK = 2;

    /**
     * Zilele saptamanii de lucru in care se programeaza ciorba: 2 = marti, 4 = joi.
     *
     * @var array<int, int>
     */
    public const SOUP_WEEKDAYS = [2, 4];

    public function scheduleChanged(DailyMeal $dailyMeal): bool
    {
        return ! $dailyMeal->exists || $dailyMeal->isDirty(self::SCHEDULE_FIELDS);
    }

    /**
     * Reguli care blocheaza salvarea: tipul si activitatea retetei, maximum doua ciorbe pe saptamana.
     *
     * @throws ValidationException
     */
    public function validate(DailyMeal $dailyMeal): void
    {
        $this->validateMenu($dailyMeal->menu_id, 'main', 'menu_id');
        $this->validateMenu($dailyMeal->soup_menu_id, 'soup', 'soup_menu_id');
        $this->validateMenu($dailyMeal->dessert_menu_id, 'dessert', 'dessert_menu_id');

        if ($dailyMeal->week_id !== null && $dailyMeal->soup_menu_id !== null && $this->otherSoupsInWeek($dailyMeal)->count() + 1 > self::SOUP_LIMIT_PER_WEEK) {
            throw ValidationException::withMessages([
                'soup_menu_id' => 'O saptamana poate avea maximum '.self::SOUP_LIMIT_PER_WEEK.' ciorbe.',
            ]);
        }
    }

    /**
     * Reguli de varietate. Nu blocheaza salvarea, ci ii semnaleaza administratorului o posibila repetare.
     *
     * @return array<int, string>
     */
    public function warnings(DailyMeal $dailyMeal): array
    {
        $warnings = [];

        $warnings = array_merge($warnings, $this->mainRecipeWarnings($dailyMeal));
        $warnings = array_merge($warnings, $this->soupWarnings($dailyMeal));

        return $warnings;
    }

    /** @return array<int, string> */
    private function mainRecipeWarnings(DailyMeal $dailyMeal): array
    {
        if ($dailyMeal->menu_id === null || $dailyMeal->meal_date === null) {
            return [];
        }

        $mealDate = CarbonImmutable::parse($dailyMeal->meal_date);
        $isTooRecent = DailyMeal::query()
            ->where('menu_id', $dailyMeal->menu_id)
            ->whereBetween('meal_date', [
                $mealDate->subDays(self::MINIMUM_DAYS_BETWEEN_MAIN_RECIPES - 1)->toDateString(),
                $mealDate->addDays(self::MINIMUM_DAYS_BETWEEN_MAIN_RECIPES - 1)->toDateString(),
            ])
            ->when($dailyMeal->exists, fn ($query) => $query->whereKeyNot($dailyMeal->id))
            ->exists();

        if ($isTooRecent) {
            return [
                sprintf(
                    'Felul principal a mai fost folosit in ultimele %d zile; se recomanda minimum %d zile intre doua preparari.',
                    self::MINIMUM_DAYS_BETWEEN_MAIN_RECIPES,
                    self::MINIMUM_DAYS_BETWEEN_MAIN_RECIPES,
                ),
            ];
        }

        return [];
    }

    /** @return array<int, string> */
    private function soupWarnings(DailyMeal $dailyMeal): array
    {
        if ($dailyMeal->soup_menu_id === null || $dailyMeal->meal_date === null) {
            return [];
        }

        $warnings = [];
        $mealDate = CarbonImmutable::parse($dailyMeal->meal_date);

        if (! in_array((int) $mealDate->isoWeekday(), self::SOUP_WEEKDAYS, true)) {
            $warnings[] = 'Ciorba este recomandata in zilele de marti si joi.';
        }

        $sameSoupInWeek = $this->otherSoupsInWeek($dailyMeal)
            ->where('soup_menu_id', $dailyMeal->soup_menu_id)
            ->isNotEmpty();

        if ($sameSoupInWeek) {
            $warnings[] = 'Aceeasi ciorba este programata de doua ori in aceeasi saptamana; se recomanda doua ciorbe diferite.';
        }

        $previousSoupDate = DailyMeal::query()
            ->whereNotNull('soup_menu_id')
            ->where('meal_date', '<', $dailyMeal->meal_date)
            ->when($dailyMeal->exists, fn ($query) => $query->whereKeyNot($dailyMeal->id))
            ->orderByDesc('meal_date')
            ->orderByDesc('id')
            ->first();

        if ($previousSoupDate?->soup_menu_id === $dailyMeal->soup_menu_id) {
            $warnings[] = 'Aceasta ciorba este programata si in ciorba imediat anterioara; se recomanda alternarea ciorbelor.';
        }

        return $warnings;
    }

    /** @return \Illuminate\Support\Collection<int, DailyMeal> */
    private function otherSoupsInWeek(DailyMeal $dailyMeal): \Illuminate\Support\Collection
    {
        return DailyMeal::query()
            ->where('week_id', $dailyMeal->week_id)
            ->whereNotNull('soup_menu_id')
            ->when($dailyMeal->exists, fn ($query) => $query->whereKeyNot($dailyMeal->id))
            ->get();
    }

    private function validateMenu(?int $menuId, string $type, string $field): void
    {
        if ($menuId === null) {
            return;
        }

        $menu = Menu::find($menuId);

        if ($menu === null || ! $menu->is_active || $menu->type !== $type) {
            throw ValidationException::withMessages([$field => 'Reteta selectata nu este disponibila pentru acest tip de masa.']);
        }
    }
}
