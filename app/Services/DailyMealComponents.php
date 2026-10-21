<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Menu;

class DailyMealComponents
{
    public function __construct(private MealRequirementCalculator $calculator) {}

    /**
     * Componentele unei zile de masa (fel principal, ciorba, desert), fiecare cu
     * meniul si necesarul calculat, ca sa nu se refaca calculul in fiecare view.
     *
     * @return array{main: array{menu: Menu|null, requirements: array|null}, soup: array{menu: Menu|null, requirements: array|null}, dessert: array{menu: Menu|null, requirements: array|null}}
     */
    public function for(DailyMeal $dailyMeal): array
    {
        $components = [];

        foreach (['main', 'soup', 'dessert'] as $key) {
            $menu = match ($key) {
                'main' => $dailyMeal->menu,
                'soup' => $dailyMeal->soupMenu,
                'dessert' => $dailyMeal->dessertMenu,
            };

            $components[$key] = [
                'menu' => $menu,
                'requirements' => $menu === null
                    ? null
                    : $this->calculator->calculate((clone $dailyMeal)->setRelation('menu', $menu)),
            ];
        }

        return $components;
    }
}
