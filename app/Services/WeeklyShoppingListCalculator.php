<?php

namespace App\Services;

use App\Models\Week;

class WeeklyShoppingListCalculator
{
    public function __construct(private MealRequirementCalculator $mealRequirementCalculator) {}

    /**
    * @return array{ingredients: list<array{name: string, quantity: float, unit: string, estimated_unit_cost: ?float, estimated_cost: float, has_missing_price: bool}>, packaging: array{count: int, total_cost: float}, daily_costs: list<array{daily_meal_id: int, ingredients_cost: float, packaging_cost: float, total_cost: float, has_missing_prices: bool}>, totals: array{ingredients_cost: float, total_cost: float, has_missing_prices: bool}, incomplete_meals: int}
     */
    public function calculate(Week $week): array
    {
        $week->loadMissing('dailyMeals.menu', 'dailyMeals.soupMenu', 'dailyMeals.dessertMenu');
        $ingredients = [];
        $packagingCount = 0;
        $packagingCost = 0.0;
        $dailyCosts = [];
        $incompleteMeals = 0;

        foreach ($week->dailyMeals as $dailyMeal) {
            if ($dailyMeal->menu === null) {
                $incompleteMeals++;

                continue;
            }

            $requirements = $this->mealRequirementCalculator->calculate($dailyMeal);
            $packagingCount += $requirements['packaging']['count'];
            $packagingCost += $requirements['packaging']['total_cost'];
            $ingredientsCost = $requirements['totals']['ingredients_cost'];
            $hasMissingPrices = $requirements['totals']['has_missing_prices'];
            $dessertPackagingCost = 0.0;

            foreach ($requirements['ingredients'] as $ingredient) {
                $this->addIngredient($ingredients, $ingredient);
            }

            if ($dailyMeal->soupMenu !== null) {
                $soupMeal = clone $dailyMeal;
                $soupMeal->setRelation('menu', $dailyMeal->soupMenu);
                $soupRequirements = $this->mealRequirementCalculator->calculate($soupMeal);

                foreach ($soupRequirements['ingredients'] as $ingredient) {
                    $this->addIngredient($ingredients, $ingredient);
                }

                $ingredientsCost += $soupRequirements['totals']['ingredients_cost'];
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

                $packagingCount += $dessertRequirements['packaging']['count'];
                $packagingCost += $dessertRequirements['packaging']['total_cost'];
                $dessertPackagingCost = $dessertRequirements['packaging']['total_cost'];
                $ingredientsCost += $dessertRequirements['totals']['ingredients_cost'];
                $hasMissingPrices = $hasMissingPrices || $dessertRequirements['totals']['has_missing_prices'];
            }

            $dailyCosts[] = [
                'daily_meal_id' => $dailyMeal->id,
                'ingredients_cost' => round($ingredientsCost, 2),
                'packaging_cost' => round($requirements['packaging']['total_cost'] + $dessertPackagingCost, 2),
                'total_cost' => round($ingredientsCost + $requirements['packaging']['total_cost'] + $dessertPackagingCost, 2),
                'has_missing_prices' => $hasMissingPrices,
            ];
        }

        $ingredients = array_values(array_map(
            fn (array $ingredient): array => [...$ingredient, 'quantity' => round($ingredient['quantity'], 3)],
            $ingredients,
        ));

        usort($ingredients, fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

        return [
            'ingredients' => $ingredients,
            'packaging' => [
                'count' => $packagingCount,
                'total_cost' => round($packagingCost, 2),
            ],
            'daily_costs' => $dailyCosts,
            'totals' => [
                'ingredients_cost' => round(array_sum(array_column($dailyCosts, 'ingredients_cost')), 2),
                'total_cost' => round(array_sum(array_column($dailyCosts, 'total_cost')), 2),
                'has_missing_prices' => collect($dailyCosts)->contains('has_missing_prices', true),
            ],
            'incomplete_meals' => $incompleteMeals,
        ];
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
        $ingredients[$key]['estimated_unit_cost'] = $ingredients[$key]['has_missing_price']
            ? null
            : ($ingredients[$key]['quantity'] > 0
                ? round($ingredients[$key]['estimated_cost'] / $ingredients[$key]['quantity'], 2)
                : ($ingredients[$key]['estimated_unit_cost'] ?? $ingredient['estimated_unit_cost']));
    }
}