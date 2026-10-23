<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\User;
use App\Models\Week;
use App\Services\DailyShoppingListCalculator;
use App\Services\MealRequirementCalculator;
use App\Services\WeeklyShoppingListCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedCostIngredientTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_migration_marks_diverse_as_a_fixed_cost_ingredient(): void
    {
        $diverse = Ingredient::query()
            ->where('name', 'Diverse (sare, ulei de gatit, condimente)')
            ->where('unit', 'buc')
            ->firstOrFail();

        $this->assertTrue((bool) $diverse->fixed_cost_per_portion);
        $this->assertSame(0.5, (float) $diverse->unit_price);
    }

    public function test_fixed_cost_ingredients_are_not_shopping_items_but_remain_in_the_totals(): void
    {
        [$meal, $week] = $this->fixedCostMeal();

        $requirements = app(MealRequirementCalculator::class)->calculate($meal);

        $this->assertSame(['Orez'], array_column($requirements['ingredients'], 'name'));
        $this->assertCount(1, $requirements['fixed_costs']);
        $this->assertSame('Diverse (cost fix)', $requirements['fixed_costs'][0]['label']);
        $this->assertSame(5.0, $requirements['fixed_costs'][0]['estimated_cost']);
        $this->assertSame(5.0, $requirements['totals']['ingredients_cost']);
        $this->assertSame(5.0, $requirements['totals']['fixed_costs_cost']);
        $this->assertSame(10.0, $requirements['totals']['total_cost']);

        $dailyShoppingList = app(DailyShoppingListCalculator::class)->for($meal);

        $this->assertSame(['Orez'], array_column($dailyShoppingList['ingredients'], 'name'));
        $this->assertSame(5.0, $dailyShoppingList['totals']['fixed_costs_cost']);
        $this->assertSame(10.0, $dailyShoppingList['totals']['total_cost']);
        $this->assertSame(1.0, $dailyShoppingList['cost_per_portion']);

        $weeklyShoppingList = app(WeeklyShoppingListCalculator::class)->calculate($week);

        $this->assertSame(['Orez'], array_column($weeklyShoppingList['ingredients'], 'name'));
        $this->assertSame(5.0, $weeklyShoppingList['totals']['fixed_costs_cost']);
        $this->assertSame(10.0, $weeklyShoppingList['totals']['total_cost']);
        $this->assertSame('Diverse (cost fix)', $weeklyShoppingList['fixed_costs'][0]['label']);
    }

    public function test_the_preparation_sheet_lists_the_fixed_cost_separately_from_the_shopping_list(): void
    {
        [$meal] = $this->fixedCostMeal();
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)
            ->get(route('daily-meal-preparation-sheets.show', $meal))
            ->assertOk()
            ->assertSee('Diverse (cost fix)')
            ->assertSee('5.00 RON')
            ->assertDontSee('Diverse (sare, ulei de gatit, condimente)');
    }

    public function test_the_weekly_report_lists_the_fixed_cost_separately_from_the_shopping_list(): void
    {
        [, $week] = $this->fixedCostMeal();
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)
            ->get(route('weekly-reports.show', $week))
            ->assertOk()
            ->assertSee('Diverse (cost fix)')
            ->assertDontSee('Diverse (sare, ulei de gatit, condimente)');
    }

    /**
     * @return array{DailyMeal, Week}
     */
    private function fixedCostMeal(): array
    {
        $congregation = Congregation::factory()->create();
        $rice = Ingredient::create(['name' => 'Orez', 'unit' => 'kg', 'unit_price' => 5, 'is_active' => true]);
        $diverse = Ingredient::query()
            ->where('name', 'Diverse (sare, ulei de gatit, condimente)')
            ->where('unit', 'buc')
            ->firstOrFail();

        $menu = Menu::create([
            'name' => 'Meniu cu cost fix',
            'type' => 'main',
            'ingredients' => [
                ['ingredient_id' => $rice->id, 'name' => $rice->name, 'unit' => $rice->unit, 'quantity_per_person' => 0.1],
                ['ingredient_id' => $diverse->id, 'name' => $diverse->name, 'unit' => $diverse->unit, 'quantity_per_person' => 1],
            ],
            'allergens' => [],
            'packaging_cost' => 0,
            'is_active' => true,
        ]);

        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $meal = DailyMeal::factory()
            ->for($week)
            ->for($congregation)
            ->for($menu)
            ->create(['meal_date' => '2026-11-30', 'estimated_people' => 10]);

        return [$meal, $week];
    }
}
