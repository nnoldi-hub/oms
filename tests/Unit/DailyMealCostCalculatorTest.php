<?php

namespace Tests\Unit;

use App\Models\DailyMeal;
use App\Models\Menu;
use App\Services\DailyMealCostCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyMealCostCalculatorTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_it_includes_an_optional_dessert_and_its_packaging_in_the_daily_cost(): void
    {
        $mainMenu = new Menu([
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 10]],
            'packaging_cost' => 2,
        ]);
        $dessertMenu = new Menu([
            'ingredients' => [['name' => 'Napolitana', 'quantity_per_person' => 1, 'unit' => 'buc', 'estimated_unit_cost' => 2.5]],
            'packaging_cost' => 0.2,
        ]);
        $dailyMeal = new DailyMeal(['estimated_people' => 20, 'dessert_menu_id' => 1]);
        $dailyMeal->setRelation('menu', $mainMenu);
        $dailyMeal->setRelation('dessertMenu', $dessertMenu);

        $cost = app(DailyMealCostCalculator::class)->calculate($dailyMeal);

        $this->assertSame(['total_cost' => 114.0, 'has_missing_prices' => false], $cost);
    }
}