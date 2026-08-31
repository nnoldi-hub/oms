<?php

namespace Tests\Unit;

use App\Models\DailyMeal;
use App\Models\Menu;
use App\Services\DailyMealCostCalculator;
use PHPUnit\Framework\TestCase;

class DailyMealCostCalculatorTest extends TestCase
{
    public function test_it_calculates_main_soup_and_main_packaging_without_double_counting_soup_packaging(): void
    {
        $mainMenu = new Menu([
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 10]],
            'packaging_cost' => 2,
        ]);
        $soupMenu = new Menu([
            'ingredients' => [['name' => 'Legume', 'quantity_per_person' => 0.2, 'unit' => 'kg', 'estimated_unit_cost' => 5]],
            'packaging_cost' => 3,
        ]);
        $dailyMeal = new DailyMeal(['estimated_people' => 20]);
        $dailyMeal->setRelation('menu', $mainMenu);
        $dailyMeal->setRelation('soupMenu', $soupMenu);

        $cost = app(DailyMealCostCalculator::class)->calculate($dailyMeal);

        $this->assertSame(['total_cost' => 80.0, 'has_missing_prices' => false], $cost);
    }
}