<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Week;
use App\Services\DailyShoppingListCalculator;
use App\Services\MealRequirementCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortionSafetyMarginTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_safety_margin_increases_the_quantities_and_packaging(): void
    {
        config(['oms.portion_safety_margin' => 0.1]);

        $meal = $this->mealFor(100);

        $requirements = app(MealRequirementCalculator::class)->calculate($meal);

        $this->assertSame(16.5, $requirements['ingredients'][0]['quantity']);
        $this->assertSame(110, $requirements['packaging']['count']);

        $shoppingList = app(DailyShoppingListCalculator::class)->for($meal);

        $this->assertSame(16.5, $shoppingList['ingredients'][0]['quantity']);
        $this->assertSame(16.5, $shoppingList['ingredients'][0]['to_buy_quantity']);
    }

    public function test_without_a_margin_the_quantities_match_the_number_of_people(): void
    {
        config(['oms.portion_safety_margin' => 0]);

        $meal = $this->mealFor(100);

        $requirements = app(MealRequirementCalculator::class)->calculate($meal);

        $this->assertSame(15.0, $requirements['ingredients'][0]['quantity']);
        $this->assertSame(100, $requirements['packaging']['count']);
    }

    public function test_the_buffered_portions_are_rounded_up_to_whole_portions(): void
    {
        config(['oms.portion_safety_margin' => 0.1]);

        $meal = $this->mealFor(33);

        $this->assertSame(37, $meal->bufferedPortions());
    }

    public function test_the_buffered_portions_fall_back_to_the_plain_portions_without_a_margin(): void
    {
        config(['oms.portion_safety_margin' => 0]);

        $meal = $this->mealFor(33);

        $this->assertSame(33, $meal->bufferedPortions());
    }

    private function mealFor(int $people): DailyMeal
    {
        $congregation = Congregation::factory()->create();
        $chicken = Ingredient::create(['name' => 'Pui', 'unit' => 'kg', 'unit_price' => 20, 'is_active' => true]);

        $menu = Menu::create([
            'name' => 'Meniu test',
            'type' => 'main',
            'ingredients' => [
                ['ingredient_id' => $chicken->id, 'name' => $chicken->name, 'unit' => $chicken->unit, 'quantity_per_person' => 0.15],
            ],
            'allergens' => [],
            'packaging_cost' => 0,
            'is_active' => true,
        ]);

        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);

        return DailyMeal::factory()
            ->for($week)
            ->for($congregation)
            ->for($menu)
            ->create(['meal_date' => '2026-11-30', 'estimated_people' => $people]);
    }
}
