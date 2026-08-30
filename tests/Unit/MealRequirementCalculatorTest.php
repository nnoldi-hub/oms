<?php

namespace Tests\Unit;

use App\Models\DailyMeal;
use App\Models\Menu;
use App\Services\MealRequirementCalculator;
use PHPUnit\Framework\TestCase;

class MealRequirementCalculatorTest extends TestCase
{
    public function test_it_aggregates_ingredients_and_calculates_packaging_for_the_estimated_people(): void
    {
        $menu = new Menu([
            'ingredients' => [
                ['name' => 'Pui', 'quantity_per_person' => 0.15, 'unit' => 'kg', 'estimated_unit_cost' => 20],
                ['name' => 'Orez', 'quantity_per_person' => 0.08, 'unit' => 'kg', 'estimated_unit_cost' => 5],
                ['name' => 'Pui', 'quantity_per_person' => 0.05, 'unit' => 'kg', 'estimated_unit_cost' => 20],
            ],
            'packaging_cost' => 1.25,
        ]);
        $dailyMeal = new DailyMeal(['estimated_people' => 50]);
        $dailyMeal->setRelation('menu', $menu);

        $requirements = app(MealRequirementCalculator::class)->calculate($dailyMeal);

        $this->assertSame([
            ['name' => 'Pui', 'quantity' => 10.0, 'unit' => 'kg', 'estimated_unit_cost' => 20.0, 'estimated_cost' => 200.0, 'has_missing_price' => false],
            ['name' => 'Orez', 'quantity' => 4.0, 'unit' => 'kg', 'estimated_unit_cost' => 5.0, 'estimated_cost' => 20.0, 'has_missing_price' => false],
        ], $requirements['ingredients']);
        $this->assertSame(['count' => 50, 'unit_cost' => 1.25, 'total_cost' => 62.5], $requirements['packaging']);
        $this->assertSame(['ingredients_cost' => 220.0, 'total_cost' => 282.5, 'has_missing_prices' => false], $requirements['totals']);
    }

    public function test_it_marks_the_total_as_partial_when_an_ingredient_has_no_price(): void
    {
        $menu = new Menu([
            'ingredients' => [['name' => 'Cartofi', 'quantity_per_person' => 0.3, 'unit' => 'kg']],
            'packaging_cost' => 1,
        ]);
        $dailyMeal = new DailyMeal(['estimated_people' => 10]);
        $dailyMeal->setRelation('menu', $menu);

        $requirements = app(MealRequirementCalculator::class)->calculate($dailyMeal);

        $this->assertTrue($requirements['ingredients'][0]['has_missing_price']);
        $this->assertSame(['ingredients_cost' => 0.0, 'total_cost' => 10.0, 'has_missing_prices' => true], $requirements['totals']);
    }

    public function test_it_calculates_a_zero_portion_meal_without_dividing_by_zero(): void
    {
        $menu = new Menu([
            'ingredients' => [[
                'name' => 'Orez',
                'quantity_per_person' => 0.1,
                'unit' => 'kg',
                'estimated_unit_cost' => 8.5,
            ]],
            'packaging_cost' => 1.15,
        ]);
        $dailyMeal = new DailyMeal(['estimated_people' => 0]);
        $dailyMeal->setRelation('menu', $menu);

        $requirements = app(MealRequirementCalculator::class)->calculate($dailyMeal);

        $this->assertSame(0.0, $requirements['ingredients'][0]['quantity']);
        $this->assertSame(8.5, $requirements['ingredients'][0]['estimated_unit_cost']);
        $this->assertSame(['ingredients_cost' => 0.0, 'total_cost' => 0.0, 'has_missing_prices' => false], $requirements['totals']);
    }
}