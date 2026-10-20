<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Menu;

class DailyMealCostCalculator
{
    public function __construct(private MealRequirementCalculator $mealRequirementCalculator) {}

    /**
     * Costul zilei, cu prioritate:
     * 1. costul real introdus manual pe masa (`actual_cost`);
     * 2. costul estimat per portie al felurilor × numarul de portii;
     * 3. calculul din ingrediente si preturi.
     *
     * @return array{total_cost: float, has_missing_prices: bool, is_partial: bool}
     */
    public function calculate(DailyMeal $dailyMeal): array
    {
        if ($dailyMeal->actual_cost !== null) {
            return [
                'total_cost' => round((float) $dailyMeal->actual_cost, 2),
                'has_missing_prices' => false,
                'is_partial' => false,
            ];
        }

        $portions = max(0, (int) $dailyMeal->estimated_people);
        $totalCost = 0.0;
        $hasMissingPrices = false;

        $components = [
            [$dailyMeal->menu, false],
            [$dailyMeal->soupMenu, true],
            [$this->dessertMenu($dailyMeal), false],
        ];

        foreach ($components as [$menu, $ingredientsOnly]) {
            if ($menu === null) {
                continue;
            }

            if ($menu->cost_per_portion !== null) {
                $totalCost += $portions * (float) $menu->cost_per_portion;

                continue;
            }

            if (blank($menu->ingredients)) {
                $hasMissingPrices = true;

                continue;
            }

            $requirements = $this->requirementsFor($dailyMeal, $menu);
            $totalCost += $ingredientsOnly
                ? $requirements['totals']['ingredients_cost']
                : $requirements['totals']['total_cost'];
            $hasMissingPrices = $hasMissingPrices || $requirements['totals']['has_missing_prices'];
        }

        if ($dailyMeal->menu === null && $dailyMeal->soupMenu === null && $this->dessertMenu($dailyMeal) === null) {
            return ['total_cost' => 0.0, 'has_missing_prices' => true, 'is_partial' => true];
        }

        return [
            'total_cost' => round($totalCost, 2),
            'has_missing_prices' => $hasMissingPrices,
            'is_partial' => $hasMissingPrices,
        ];
    }

    /**
     * @return array{ingredients: list<array<string, mixed>>, packaging: array<string, mixed>, totals: array{ingredients_cost: float, total_cost: float, has_missing_prices: bool}}
     */
    private function requirementsFor(DailyMeal $dailyMeal, Menu $menu): array
    {
        $clone = clone $dailyMeal;
        $clone->setRelation('menu', $menu);

        return $this->mealRequirementCalculator->calculate($clone);
    }

    private function dessertMenu(DailyMeal $dailyMeal): ?Menu
    {
        return $dailyMeal->relationLoaded('dessertMenu')
            ? $dailyMeal->getRelation('dessertMenu')
            : $dailyMeal->dessertMenu;
    }
}
