<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Ingredient;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class MealRequirementCalculator
{
    /**
    * @return array{ingredients: list<array{name: string, quantity: float, unit: string, estimated_unit_cost: ?float, estimated_cost: float, has_missing_price: bool}>, packaging: array{count: int, unit_cost: float, total_cost: float}, totals: array{ingredients_cost: float, total_cost: float, has_missing_prices: bool}}
     */
    public function calculate(DailyMeal $dailyMeal): array
    {
        $menu = $dailyMeal->menu;

        if ($menu === null) {
            throw new InvalidArgumentException('O zi de masa necesita un meniu pentru calcul.');
        }

        $requirements = [];
        $ingredientIds = collect($menu->ingredients)->pluck('ingredient_id')->filter()->unique();
        $catalogIngredients = $ingredientIds->isEmpty()
            ? collect()
            : Ingredient::query()->whereIn('id', $ingredientIds)->get()->keyBy('id');

        foreach ($menu->ingredients as $recipeIngredient) {
            $catalogIngredient = $catalogIngredients->get(Arr::get($recipeIngredient, 'ingredient_id'));
            $name = $catalogIngredient?->name ?? trim((string) Arr::get($recipeIngredient, 'name'));
            $unit = $catalogIngredient?->unit ?? (string) Arr::get($recipeIngredient, 'unit');
            $key = mb_strtolower($name).'|'.$unit;
            $quantity = $dailyMeal->estimated_people * (float) Arr::get($recipeIngredient, 'quantity_per_person');
            $unitCost = $catalogIngredient?->unit_price ?? Arr::get($recipeIngredient, 'estimated_unit_cost');
            $hasPrice = is_numeric($unitCost) && (float) $unitCost >= 0;

            if (! isset($requirements[$key])) {
                $requirements[$key] = [
                    'name' => $name,
                    'quantity' => 0.0,
                    'unit' => $unit,
                    'estimated_cost' => 0.0,
                    'unpriced_quantity' => 0.0,
                    'configured_unit_cost' => $hasPrice ? (float) $unitCost : null,
                ];
            }

            $requirements[$key]['quantity'] += $quantity;
            $requirements[$key]['estimated_cost'] += $hasPrice ? $quantity * (float) $unitCost : 0;
            $requirements[$key]['unpriced_quantity'] += $hasPrice ? 0 : $quantity;
        }

        $ingredients = array_values(array_map(
            function (array $requirement): array {
                $hasMissingPrice = $requirement['unpriced_quantity'] > 0;

                return [
                    'name' => $requirement['name'],
                    'quantity' => round($requirement['quantity'], 3),
                    'unit' => $requirement['unit'],
                    'estimated_unit_cost' => $hasMissingPrice
                        ? null
                        : ($requirement['quantity'] > 0
                            ? round($requirement['estimated_cost'] / $requirement['quantity'], 2)
                            : $requirement['configured_unit_cost']),
                    'estimated_cost' => round($requirement['estimated_cost'], 2),
                    'has_missing_price' => $hasMissingPrice,
                ];
            },
            $requirements,
        ));

        $ingredientsCost = round(array_sum(array_column($ingredients, 'estimated_cost')), 2);
        $packagingCost = round($dailyMeal->estimated_people * (float) $menu->packaging_cost, 2);

        return [
            'ingredients' => $ingredients,
            'packaging' => [
                'count' => $dailyMeal->estimated_people,
                'unit_cost' => (float) $menu->packaging_cost,
                'total_cost' => $packagingCost,
            ],
            'totals' => [
                'ingredients_cost' => $ingredientsCost,
                'total_cost' => round($ingredientsCost + $packagingCost, 2),
                'has_missing_prices' => collect($ingredients)->contains('has_missing_price', true),
            ],
        ];
    }
}