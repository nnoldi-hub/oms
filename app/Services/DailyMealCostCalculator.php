<?php

namespace App\Services;

use App\Models\DailyMeal;

class DailyMealCostCalculator
{
    public function __construct(private MealRequirementCalculator $mealRequirementCalculator) {}

    /**
     * @return array{total_cost: float, has_missing_prices: bool}
     */
    public function calculate(DailyMeal $dailyMeal): array
    {
        if ($dailyMeal->menu === null) {
            return ['total_cost' => 0.0, 'has_missing_prices' => true];
        }

        $mainRequirements = $this->mealRequirementCalculator->calculate($dailyMeal);
        $totalCost = $mainRequirements['totals']['total_cost'];
        $hasMissingPrices = $mainRequirements['totals']['has_missing_prices'];

        if ($dailyMeal->soupMenu !== null) {
            $soupMeal = clone $dailyMeal;
            $soupMeal->setRelation('menu', $dailyMeal->soupMenu);
            $soupRequirements = $this->mealRequirementCalculator->calculate($soupMeal);
            $totalCost += $soupRequirements['totals']['ingredients_cost'];
            $hasMissingPrices = $hasMissingPrices || $soupRequirements['totals']['has_missing_prices'];
        }

        return ['total_cost' => round($totalCost, 2), 'has_missing_prices' => $hasMissingPrices];
    }
}