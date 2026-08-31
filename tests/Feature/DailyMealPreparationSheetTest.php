<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\User;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyMealPreparationSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_coordinator_can_print_only_their_days_preparation_sheet(): void
    {
        $congregation = Congregation::factory()->create(['name' => 'Congregatia Est']);
        $mainMenu = Menu::factory()->create(['name' => 'Mancare de cartofi', 'ingredients' => [['name' => 'Cartofi', 'quantity_per_person' => 0.2, 'unit' => 'kg', 'estimated_unit_cost' => 5]], 'allergens' => ['Telina'], 'packaging_cost' => 1]);
        $soupMenu = Menu::factory()->create(['name' => 'Ciorba de legume', 'type' => 'soup', 'ingredients' => [['name' => 'Morcovi', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 1]], 'packaging_cost' => 0]);
        $dessertMenu = Menu::factory()->create(['name' => 'Napolitana', 'type' => 'dessert', 'ingredients' => [['name' => 'Napolitana', 'quantity_per_person' => 1, 'unit' => 'buc', 'estimated_unit_cost' => 1]], 'packaging_cost' => 0]);
        $dailyMeal = DailyMeal::factory()->for(Week::factory()->for($congregation))->for($congregation)->for($mainMenu)->create(['meal_date' => '2026-12-09', 'estimated_people' => 30, 'soup_menu_id' => $soupMenu->id, 'dessert_menu_id' => $dessertMenu->id, 'maximum_budget' => 300, 'contributor_count' => 3]);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $this->actingAs($coordinator)
            ->get(route('daily-meal-preparation-sheets.show', $dailyMeal))
            ->assertOk()
            ->assertSee('fisa de pregatire')
            ->assertSee('Congregatia Est')
            ->assertSee('Mancare de cartofi')
            ->assertSee('Ciorba de legume')
            ->assertSee('Napolitana')
            ->assertSee('6 kg')
            ->assertSee('3 kg')
            ->assertSee('Telina')
            ->assertSee('3.10 RON/portie')
            ->assertDontSee('@if');
    }

    public function test_a_coordinator_cannot_print_another_congregations_day(): void
    {
        $assignedCongregation = Congregation::factory()->create();
        $otherCoordinator = User::factory()->for(Congregation::factory())->create(['role' => 'coordinator']);
        $dailyMeal = DailyMeal::factory()->for(Week::factory()->for($assignedCongregation))->for($assignedCongregation)->for(Menu::factory())->create();

        $this->actingAs($otherCoordinator)
            ->get(route('daily-meal-preparation-sheets.show', $dailyMeal))
            ->assertForbidden();
    }
}