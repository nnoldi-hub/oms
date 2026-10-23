<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\DailyMealCostSnapshot;
use App\Models\Menu;

class DailyMealCostCalculator
{
    public function __construct(private MealRequirementCalculator $mealRequirementCalculator) {}

    /**
     * Costul zilei, cu prioritate:
     * 1. snapshot-ul unei saptamani inchise (rapoartele vechi nu se schimba);
     * 2. costul real introdus manual pe masa (`actual_cost`);
     * 3. costul estimat per portie al felurilor × numarul de portii;
     * 4. calculul din ingrediente si preturi.
     *
     * @return array{total_cost: float, has_missing_prices: bool, is_partial: bool}
     */
    public function calculate(DailyMeal $dailyMeal): array
    {
        if ($snapshot = $this->snapshotFor($dailyMeal)) {
            return $snapshot->toCostArray();
        }

        $result = $this->calculateFresh($dailyMeal);
        unset($result['breakdown']);

        return $result;
    }

    /**
     * Calculul curent, fara a citi snapshot-ul existent.
     *
     * @return array{total_cost: float, has_missing_prices: bool, is_partial: bool, breakdown: array{ingredients_cost: float, fixed_costs_cost: float, packaging_cost: float}}
     */
    public function calculateFresh(DailyMeal $dailyMeal): array
    {
        $portions = $dailyMeal->portions();

        if ($dailyMeal->actual_cost !== null) {
            $totalCost = round((float) $dailyMeal->actual_cost, 2);

            return [
                'total_cost' => $totalCost,
                'has_missing_prices' => false,
                'is_partial' => false,
                'breakdown' => [
                    'ingredients_cost' => $totalCost,
                    'fixed_costs_cost' => 0.0,
                    'packaging_cost' => 0.0,
                ],
            ];
        }

        $ingredientsCost = 0.0;
        $fixedCostsCost = 0.0;
        $packagingCost = 0.0;
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
                $ingredientsCost += $portions * (float) $menu->cost_per_portion;

                continue;
            }

            if (blank($menu->ingredients)) {
                $hasMissingPrices = true;

                continue;
            }

            $requirements = $this->requirementsFor($dailyMeal, $menu);
            $ingredientsCost += $requirements['totals']['ingredients_cost'];
            $fixedCostsCost += $requirements['totals']['fixed_costs_cost'] ?? 0.0;
            $packagingCost += $ingredientsOnly ? 0.0 : $requirements['packaging']['total_cost'];
            $hasMissingPrices = $hasMissingPrices || $requirements['totals']['has_missing_prices'];
        }

        if ($dailyMeal->menu === null && $dailyMeal->soupMenu === null && $this->dessertMenu($dailyMeal) === null) {
            return [
                'total_cost' => 0.0,
                'has_missing_prices' => true,
                'is_partial' => true,
                'breakdown' => [
                    'ingredients_cost' => 0.0,
                    'fixed_costs_cost' => 0.0,
                    'packaging_cost' => 0.0,
                ],
            ];
        }

        $ingredientsCost = round($ingredientsCost, 2);
        $fixedCostsCost = round($fixedCostsCost, 2);
        $packagingCost = round($packagingCost, 2);

        return [
            'total_cost' => round($ingredientsCost + $fixedCostsCost + $packagingCost, 2),
            'has_missing_prices' => $hasMissingPrices,
            'is_partial' => $hasMissingPrices,
            'breakdown' => [
                'ingredients_cost' => $ingredientsCost,
                'fixed_costs_cost' => $fixedCostsCost,
                'packaging_cost' => $packagingCost,
            ],
        ];
    }

    public function snapshotFor(DailyMeal $dailyMeal): ?DailyMealCostSnapshot
    {
        if (! $dailyMeal->exists) {
            return null;
        }

        if ($dailyMeal->relationLoaded('costSnapshot')) {
            $snapshot = $dailyMeal->getRelation('costSnapshot');

            return $snapshot instanceof DailyMealCostSnapshot ? $snapshot : null;
        }

        return $dailyMeal->costSnapshot()->first();
    }

    /**
     * @return array{ingredients: list<array<string, mixed>>, fixed_costs: list<array<string, mixed>>, packaging: array<string, mixed>, totals: array{ingredients_cost: float, fixed_costs_cost: float, total_cost: float, has_missing_prices: bool}}
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
