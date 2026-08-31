<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Menu;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class MenuSchedulingValidator
{
    public function validate(DailyMeal $dailyMeal): void
    {
        $this->validateMenu($dailyMeal, $dailyMeal->menu_id, 'main', 'menu_id');
        $this->validateMenu($dailyMeal, $dailyMeal->soup_menu_id, 'soup', 'soup_menu_id');
        $this->validateMenu($dailyMeal, $dailyMeal->dessert_menu_id, 'dessert', 'dessert_menu_id');

        if ($dailyMeal->soup_menu_id !== null && $dailyMeal->week_id !== null) {
            $anotherSoupExists = DailyMeal::query()
                ->where('week_id', $dailyMeal->week_id)
                ->whereNotNull('soup_menu_id')
                ->when($dailyMeal->exists, fn ($query) => $query->whereKeyNot($dailyMeal->id))
                ->exists();

            if ($anotherSoupExists) {
                throw ValidationException::withMessages([
                    'soup_menu_id' => 'O singura ciorba poate fi programata intr-o saptamana.',
                ]);
            }
        }
    }

    private function validateMenu(DailyMeal $dailyMeal, ?int $menuId, string $type, string $field): void
    {
        if ($menuId === null) {
            return;
        }

        $menu = Menu::find($menuId);

        if ($menu === null || ! $menu->is_active || $menu->type !== $type) {
            throw ValidationException::withMessages([$field => 'Reteta selectata nu este disponibila pentru acest tip de masa.']);
        }

        $congregation = $dailyMeal->congregation;

        if (
            $congregation !== null
            && $congregation->menus()->exists()
            && ! $menu->congregations()->whereKey($congregation->id)->exists()
        ) {
            throw ValidationException::withMessages([$field => 'Reteta nu este aprobata de congregatia responsabila.']);
        }

        if ($type !== 'main' || $dailyMeal->meal_date === null) {
            return;
        }

        $mealDate = CarbonImmutable::parse($dailyMeal->meal_date);
        $isTooRecent = DailyMeal::query()
            ->where('menu_id', $menuId)
            ->whereBetween('meal_date', [$mealDate->subDays(5), $mealDate->addDays(5)])
            ->when($dailyMeal->exists, fn ($query) => $query->whereKeyNot($dailyMeal->id))
            ->exists();

        if ($isTooRecent) {
            throw ValidationException::withMessages([
                'menu_id' => 'Aceasta reteta a fost pregatita prea recent. Lasa minimum 6 zile intre doua preparari.',
            ]);
        }
    }
}