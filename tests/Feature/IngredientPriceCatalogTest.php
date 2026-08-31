<?php

namespace Tests\Feature;

use App\Models\DailyMeal;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\User;
use App\Services\MealRequirementCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientPriceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_global_ingredient_price_updates_recipe_costs_without_editing_the_recipe(): void
    {
        $flour = Ingredient::factory()->create(['name' => 'Faina', 'unit' => 'kg', 'unit_price' => 4]);
        $menu = Menu::factory()->create([
            'ingredients' => [['ingredient_id' => $flour->id, 'name' => 'Faina', 'unit' => 'kg', 'quantity_per_person' => 0.1]],
            'packaging_cost' => 0,
        ]);
        $meal = DailyMeal::factory()->for($menu)->create(['estimated_people' => 10]);

        $this->assertSame(4.0, app(MealRequirementCalculator::class)->calculate($meal)['totals']['total_cost']);

        $flour->update(['unit_price' => 6.5]);

        $this->assertSame(6.5, app(MealRequirementCalculator::class)->calculate($meal->fresh()->load('menu'))['totals']['total_cost']);
    }

    public function test_kitchen_users_can_open_the_recipe_cost_report_but_coordinators_cannot(): void
    {
        Ingredient::factory()->create(['name' => 'Orez', 'unit' => 'kg', 'unit_price' => 8]);
        Menu::factory()->create(['name' => 'Pilaf test', 'ingredients' => [['name' => 'Orez', 'unit' => 'kg', 'quantity_per_person' => 0.1]]]);

        $this->actingAs(User::factory()->create(['role' => 'kitchen']))
            ->get(route('menu-cost-reports.show'))
            ->assertOk()
            ->assertSee('Raport retete si costuri')
            ->assertSee('Pilaf test');

        $this->actingAs(User::factory()->create(['role' => 'coordinator']))
            ->get(route('menu-cost-reports.show'))
            ->assertForbidden();
    }
}