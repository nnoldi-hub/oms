<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Ingredient;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MealRequirementCalculator
{
    /**
     * @return array{ingredients: list<array{name: string, quantity: float, unit: string, estimated_unit_cost: ?float, estimated_cost: float, has_missing_price: bool, needs_configuration: bool, package_quantity: ?float}>, fixed_costs: list<array{name: string, quantity: float, unit: string, estimated_unit_cost: ?float, estimated_cost: float, has_missing_price: bool}>, packaging: array{count: int, unit_cost: float, total_cost: float}, totals: array{ingredients_cost: float, fixed_costs_cost: float, total_cost: float, has_missing_prices: bool}}
     */
    public function calculate(DailyMeal $dailyMeal): array
    {
        $menu = $dailyMeal->menu;

        if ($menu === null) {
            throw new InvalidArgumentException('O zi de masa necesita un meniu pentru calcul.');
        }

        $requirements = [];
        $fixedRequirements = [];
        $ingredientIds = collect($menu->ingredients)->pluck('ingredient_id')->filter()->unique();
        $catalogIngredients = $ingredientIds->isEmpty()
            ? collect()
            : Ingredient::query()->whereIn('id', $ingredientIds)->get()->keyBy('id');

        foreach ($menu->ingredients as $recipeIngredient) {
            $catalogIngredient = $catalogIngredients->get(Arr::get($recipeIngredient, 'ingredient_id'));
            $name = $catalogIngredient?->name ?? trim((string) Arr::get($recipeIngredient, 'name'));
            $unit = $catalogIngredient?->unit ?? (string) Arr::get($recipeIngredient, 'unit');
            $key = mb_strtolower($name).'|'.$unit;
            $quantity = $dailyMeal->portions() * (float) Arr::get($recipeIngredient, 'quantity_per_person');
            $unitCost = $catalogIngredient?->unit_price ?? Arr::get($recipeIngredient, 'estimated_unit_cost');
            $hasPrice = is_numeric($unitCost) && (float) $unitCost >= 0;
            $needsConfiguration = (bool) Arr::get($recipeIngredient, 'needs_configuration', false);

            if ((bool) $catalogIngredient?->fixed_cost_per_portion) {
                if (! isset($fixedRequirements[$key])) {
                    $fixedRequirements[$key] = [
                        'name' => $name,
                        'quantity' => 0.0,
                        'unit' => $unit,
                        'estimated_cost' => 0.0,
                        'unpriced_quantity' => 0.0,
                        'configured_unit_cost' => $hasPrice ? (float) $unitCost : null,
                        'needs_configuration' => false,
                    ];
                }

                $fixedRequirements[$key]['quantity'] += $quantity;
                $fixedRequirements[$key]['estimated_cost'] += $hasPrice ? $quantity * (float) $unitCost : 0;
                $fixedRequirements[$key]['unpriced_quantity'] += $hasPrice ? 0 : $quantity;
                $fixedRequirements[$key]['needs_configuration'] = $fixedRequirements[$key]['needs_configuration'] || $needsConfiguration;

                continue;
            }

            if (! isset($requirements[$key])) {
                $requirements[$key] = [
                    'name' => $name,
                    'quantity' => 0.0,
                    'unit' => $unit,
                    'estimated_cost' => 0.0,
                    'unpriced_quantity' => 0.0,
                    'configured_unit_cost' => $hasPrice ? (float) $unitCost : null,
                    'needs_configuration' => false,
                    'package_quantity' => $catalogIngredient?->packageQuantity(),
                ];
            }

            $requirements[$key]['quantity'] += $quantity;
            $requirements[$key]['estimated_cost'] += $hasPrice ? $quantity * (float) $unitCost : 0;
            $requirements[$key]['unpriced_quantity'] += $hasPrice ? 0 : $quantity;
            $requirements[$key]['needs_configuration'] = $requirements[$key]['needs_configuration'] || $needsConfiguration;
        }

        $ingredients = array_values(array_map(
            function (array $requirement): array {
                $hasMissingPrice = $requirement['unpriced_quantity'] > 0 || $requirement['needs_configuration'];

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
                    'needs_configuration' => $requirement['needs_configuration'],
                    'package_quantity' => $requirement['package_quantity'],
                ];
            },
            $requirements,
        ));

        $fixedCosts = array_values(array_map(
            function (array $requirement): array {
                $hasMissingPrice = $requirement['unpriced_quantity'] > 0 || $requirement['needs_configuration'];

                return [
                    'name' => $requirement['name'],
                    'label' => Str::before($requirement['name'], ' (').' (cost fix)',
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
            $fixedRequirements,
        ));

        $ingredientsCost = round(array_sum(array_column($ingredients, 'estimated_cost')), 2);
        $fixedCostsCost = round(array_sum(array_column($fixedCosts, 'estimated_cost')), 2);
        $packagingCost = round($dailyMeal->portions() * (float) $menu->packaging_cost, 2);

        return [
            'ingredients' => $ingredients,
            'fixed_costs' => $fixedCosts,
            'packaging' => [
                'count' => $dailyMeal->portions(),
                'unit_cost' => (float) $menu->packaging_cost,
                'total_cost' => $packagingCost,
            ],
            'totals' => [
                'ingredients_cost' => $ingredientsCost,
                'fixed_costs_cost' => $fixedCostsCost,
                'total_cost' => round($ingredientsCost + $fixedCostsCost + $packagingCost, 2),
                'has_missing_prices' => collect($ingredients)->contains('has_missing_price', true)
                    || collect($fixedCosts)->contains('has_missing_price', true),
            ],
        ];
    }
}
