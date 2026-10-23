<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Week;
use App\Support\PracticalQuantity;

class WeeklyShoppingListCalculator
{
    public function __construct(
        private MealRequirementCalculator $mealRequirementCalculator,
        private DailyMealCostSnapshotService $snapshotService,
    ) {}

    /**
     * @return array{ingredients: list<array{name: string, quantity: float, to_buy_quantity: float, unit: string, estimated_unit_cost: ?float, estimated_cost: float, has_missing_price: bool}>, fixed_costs: list<array{name: string, quantity: float, unit: string, estimated_unit_cost: ?float, estimated_cost: float, has_missing_price: bool}>, packaging: array{count: int, total_cost: float}, daily_costs: list<array{daily_meal_id: int, ingredients_cost: float, fixed_costs_cost: float, packaging_cost: float, total_cost: float, has_missing_prices: bool}>, totals: array{ingredients_cost: float, fixed_costs_cost: float, total_cost: float, has_missing_prices: bool}, incomplete_meals: int}
     */
    public function calculate(Week $week): array
    {
        $this->snapshotService->snapshotWeekIfClosed($week);
        $week->loadMissing('dailyMeals.menu', 'dailyMeals.soupMenu', 'dailyMeals.dessertMenu', 'dailyMeals.costSnapshot');
        $ingredients = [];
        $fixedCosts = [];
        $packagingCount = 0;
        $packagingCost = 0.0;
        $dailyCosts = [];
        $incompleteMeals = 0;

        foreach ($week->dailyMeals as $dailyMeal) {
            if (! $this->isConfigured($dailyMeal)) {
                $incompleteMeals++;

                continue;
            }

            $requirements = $this->mealRequirementCalculator->calculate($dailyMeal);
            $packagingCount += $requirements['packaging']['count'];
            $packagingCost += $requirements['packaging']['total_cost'];
            $ingredientsCost = $requirements['totals']['ingredients_cost'];
            $fixedCostsCost = $requirements['totals']['fixed_costs_cost'];
            $hasMissingPrices = $requirements['totals']['has_missing_prices'];
            $dessertPackagingCost = 0.0;

            foreach ($requirements['ingredients'] as $ingredient) {
                $this->addIngredient($ingredients, $ingredient);
            }

            foreach ($requirements['fixed_costs'] as $fixedCost) {
                $this->addFixedCost($fixedCosts, $fixedCost);
            }

            if ($dailyMeal->soupMenu !== null) {
                $soupMeal = clone $dailyMeal;
                $soupMeal->setRelation('menu', $dailyMeal->soupMenu);
                $soupRequirements = $this->mealRequirementCalculator->calculate($soupMeal);

                foreach ($soupRequirements['ingredients'] as $ingredient) {
                    $this->addIngredient($ingredients, $ingredient);
                }

                foreach ($soupRequirements['fixed_costs'] as $fixedCost) {
                    $this->addFixedCost($fixedCosts, $fixedCost);
                }

                $ingredientsCost += $soupRequirements['totals']['ingredients_cost'];
                $fixedCostsCost += $soupRequirements['totals']['fixed_costs_cost'];
                $hasMissingPrices = $hasMissingPrices || $soupRequirements['totals']['has_missing_prices'];
            }

            if ($dailyMeal->dessert_menu_id !== null) {
                $dessertMenu = $dailyMeal->relationLoaded('dessertMenu')
                    ? $dailyMeal->getRelation('dessertMenu')
                    : $dailyMeal->dessertMenu;
                $dessertMeal = clone $dailyMeal;
                $dessertMeal->setRelation('menu', $dessertMenu);
                $dessertRequirements = $this->mealRequirementCalculator->calculate($dessertMeal);

                foreach ($dessertRequirements['ingredients'] as $ingredient) {
                    $this->addIngredient($ingredients, $ingredient);
                }

                foreach ($dessertRequirements['fixed_costs'] as $fixedCost) {
                    $this->addFixedCost($fixedCosts, $fixedCost);
                }

                $packagingCount += $dessertRequirements['packaging']['count'];
                $packagingCost += $dessertRequirements['packaging']['total_cost'];
                $dessertPackagingCost = $dessertRequirements['packaging']['total_cost'];
                $ingredientsCost += $dessertRequirements['totals']['ingredients_cost'];
                $fixedCostsCost += $dessertRequirements['totals']['fixed_costs_cost'];
                $hasMissingPrices = $hasMissingPrices || $dessertRequirements['totals']['has_missing_prices'];
            }

            $snapshot = $dailyMeal->relationLoaded('costSnapshot')
                ? $dailyMeal->getRelation('costSnapshot')
                : $dailyMeal->costSnapshot;

            $dailyCosts[] = $snapshot !== null
                ? [
                    'daily_meal_id' => $dailyMeal->id,
                    'ingredients_cost' => (float) $snapshot->ingredients_cost,
                    'fixed_costs_cost' => (float) $snapshot->fixed_costs_cost,
                    'packaging_cost' => (float) $snapshot->packaging_cost,
                    'total_cost' => (float) $snapshot->total_cost,
                    'has_missing_prices' => (bool) $snapshot->has_missing_prices,
                ]
                : [
                    'daily_meal_id' => $dailyMeal->id,
                    'ingredients_cost' => round($ingredientsCost, 2),
                    'fixed_costs_cost' => round($fixedCostsCost, 2),
                    'packaging_cost' => round($requirements['packaging']['total_cost'] + $dessertPackagingCost, 2),
                    'total_cost' => round($ingredientsCost + $fixedCostsCost + $requirements['packaging']['total_cost'] + $dessertPackagingCost, 2),
                    'has_missing_prices' => $hasMissingPrices,
                ];
        }

        $ingredients = array_values(array_map(
            fn (array $ingredient): array => [
                ...$ingredient,
                'quantity' => round($ingredient['quantity'], 3),
                'to_buy_quantity' => PracticalQuantity::roundUp(
                    round($ingredient['quantity'], 3),
                    $ingredient['unit'],
                    $ingredient['package_quantity'] ?? null,
                ),
            ],
            $ingredients,
        ));

        usort($ingredients, fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

        $fixedCosts = array_values($fixedCosts);
        usort($fixedCosts, fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

        return [
            'ingredients' => $ingredients,
            'fixed_costs' => $fixedCosts,
            'packaging' => [
                'count' => $packagingCount,
                'total_cost' => round($packagingCost, 2),
            ],
            'daily_costs' => $dailyCosts,
            'totals' => [
                'ingredients_cost' => round(array_sum(array_column($dailyCosts, 'ingredients_cost')), 2),
                'fixed_costs_cost' => round(array_sum(array_column($dailyCosts, 'fixed_costs_cost')), 2),
                'total_cost' => round(array_sum(array_column($dailyCosts, 'total_cost')), 2),
                'has_missing_prices' => collect($dailyCosts)->contains('has_missing_prices', true),
            ],
            'incomplete_meals' => $incompleteMeals,
        ];
    }

    private function isConfigured(DailyMeal $dailyMeal): bool
    {
        if ($dailyMeal->menu === null) {
            return false;
        }

        foreach ([$dailyMeal->menu, $dailyMeal->soupMenu, $dailyMeal->dessertMenu] as $menu) {
            if ($menu !== null && (filled($menu->ingredients) || $menu->cost_per_portion !== null)) {
                return true;
            }
        }

        return false;
    }

    private function addIngredient(array &$ingredients, array $ingredient): void
    {
        $key = mb_strtolower($ingredient['name']).'|'.$ingredient['unit'];

        if (! isset($ingredients[$key])) {
            $ingredients[$key] = $ingredient;

            return;
        }

        $ingredients[$key]['quantity'] += $ingredient['quantity'];
        $ingredients[$key]['estimated_cost'] += $ingredient['estimated_cost'];
        $ingredients[$key]['has_missing_price'] = $ingredients[$key]['has_missing_price'] || $ingredient['has_missing_price'];
        $ingredients[$key]['needs_configuration'] = ($ingredients[$key]['needs_configuration'] ?? false)
            || ($ingredient['needs_configuration'] ?? false);
        $ingredients[$key]['package_quantity'] = $ingredients[$key]['package_quantity']
            ?? ($ingredient['package_quantity'] ?? null);
        $ingredients[$key]['estimated_unit_cost'] = $ingredients[$key]['has_missing_price']
            ? null
            : ($ingredients[$key]['quantity'] > 0
                ? round($ingredients[$key]['estimated_cost'] / $ingredients[$key]['quantity'], 2)
                : ($ingredients[$key]['estimated_unit_cost'] ?? $ingredient['estimated_unit_cost']));
    }

    private function addFixedCost(array &$fixedCosts, array $fixedCost): void
    {
        $key = mb_strtolower($fixedCost['name']).'|'.$fixedCost['unit'];

        if (! isset($fixedCosts[$key])) {
            $fixedCosts[$key] = $fixedCost;

            return;
        }

        $fixedCosts[$key]['quantity'] += $fixedCost['quantity'];
        $fixedCosts[$key]['estimated_cost'] += $fixedCost['estimated_cost'];
        $fixedCosts[$key]['has_missing_price'] = $fixedCosts[$key]['has_missing_price'] || $fixedCost['has_missing_price'];
    }
}
