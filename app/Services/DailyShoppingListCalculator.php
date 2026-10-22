<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Support\PracticalQuantity;

class DailyShoppingListCalculator
{
    public function __construct(private DailyMealComponents $components) {}

    /**
     * Lista de cumparaturi a unei zile: ingredientele din toate felurile zilei,
     * cantitatile rotunjite practic si totalurile (ingrediente, ambalaje, cost pe portie).
     *
     * @return array{ingredients: list<array{name: string, quantity: float, to_buy_quantity: float, unit: string, estimated_unit_cost: ?float, estimated_cost: float, has_missing_price: bool, needs_configuration: bool}>, packaging: array{count: int, total_cost: float}, totals: array{ingredients_cost: float, packaging_cost: float, total_cost: float, has_missing_prices: bool}, cost_per_portion: float|null}
     */
    public function for(DailyMeal $dailyMeal): array
    {
        $components = collect($this->components->for($dailyMeal))
            ->filter(fn (array $component): bool => $component['menu'] !== null);

        $ingredients = [];
        $packagingCount = 0;
        $packagingCost = 0.0;

        foreach ($components as $componentKey => $component) {
            $requirements = $component['requirements'];

            foreach ($requirements['ingredients'] as $ingredient) {
                $this->addIngredient($ingredients, $ingredient);
            }

            // Ciorba se ambaleaza in acelasi container ca felul principal.
            if ($componentKey === 'soup') {
                continue;
            }

            $packagingCount += (int) $requirements['packaging']['count'];
            $packagingCost += (float) $requirements['packaging']['total_cost'];
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

        $ingredientsCost = round(array_sum(array_column($ingredients, 'estimated_cost')), 2);
        $hasMissingPrices = collect($ingredients)->contains('has_missing_price', true);
        $portions = $dailyMeal->portions();
        $totalCost = round($ingredientsCost + $packagingCost, 2);

        return [
            'ingredients' => $ingredients,
            'packaging' => [
                'count' => $packagingCount,
                'total_cost' => round($packagingCost, 2),
            ],
            'totals' => [
                'ingredients_cost' => $ingredientsCost,
                'packaging_cost' => round($packagingCost, 2),
                'total_cost' => $totalCost,
                'has_missing_prices' => $hasMissingPrices,
            ],
            'cost_per_portion' => $portions > 0 && ! $hasMissingPrices
                ? round($totalCost / $portions, 2)
                : null,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $ingredients
     * @param  array<string, mixed>  $ingredient
     */
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
    }
}
