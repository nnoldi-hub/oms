<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\User;
use App\Models\Volunteer;
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
            ->assertSee('fișă de pregătire')
            ->assertSee('Congregatia Est')
            ->assertSee('Mancare de cartofi')
            ->assertSee('Ciorba de legume')
            ->assertSee('Napolitana')
            ->assertSee('6 kg')
            ->assertSee('3 kg')
            ->assertSee('Telina')
            ->assertSee('3.10 RON')
            ->assertSee('RON / porție')
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

    public function test_the_sheet_ends_with_totals_and_the_shopping_list(): void
    {
        $congregation = Congregation::factory()->create(['name' => 'Congregatia Nord']);
        $mainMenu = Menu::factory()->create([
            'name' => 'Paine cu cascaval',
            'ingredients' => [
                ['name' => 'Paine', 'quantity_per_person' => 0.11, 'unit' => 'kg', 'estimated_unit_cost' => 10],
            ],
            'allergens' => ['Gluten'],
            'packaging_cost' => 0.5,
        ]);
        $dailyMeal = DailyMeal::factory()
            ->for(Week::factory()->for($congregation))
            ->for($congregation)
            ->for($mainMenu)
            ->create(['meal_date' => '2026-12-10', 'estimated_people' => 30]);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $response = $this->actingAs($coordinator)
            ->get(route('daily-meal-preparation-sheets.show', $dailyMeal))
            ->assertOk()
            ->assertSee('Totaluri și de cumpărat')
            ->assertSee('Total ingrediente:')
            ->assertSee('Total ambalaje:')
            ->assertSee('Cost pe porție:')
            ->assertSee('De cumpărat')
            ->assertSee('Paine');

        // Cantitatea rotunjita practic pentru cumparaturi: 30 * 0.11 kg = 3.3 kg -> 3.5 kg.
        $response->assertSee('3.3 kg')->assertSee('3.5 kg');
    }

    public function test_the_sheet_marks_undeclared_allergens_for_verification(): void
    {
        $congregation = Congregation::factory()->create();
        $mainMenu = Menu::factory()->create([
            'name' => 'Omleta',
            'ingredients' => [['name' => 'Oua', 'quantity_per_person' => 2, 'unit' => 'buc', 'estimated_unit_cost' => 1]],
            'allergens' => [],
            'packaging_cost' => 0,
        ]);
        $dailyMeal = DailyMeal::factory()
            ->for(Week::factory()->for($congregation))
            ->for($congregation)
            ->for($mainMenu)
            ->create(['meal_date' => '2026-12-11', 'estimated_people' => 10]);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $this->actingAs($coordinator)
            ->get(route('daily-meal-preparation-sheets.show', $dailyMeal))
            ->assertOk()
            ->assertSee('Nedeclarat – de verificat')
            ->assertDontSee('Niciunul declarat')
            ->assertDontSee('Niciunul');
    }

    public function test_the_sheet_shows_no_packaging_when_the_packaging_cost_is_zero(): void
    {
        $congregation = Congregation::factory()->create();
        $mainMenu = Menu::factory()->create([
            'name' => 'Mamaliga',
            'ingredients' => [['name' => 'Malai', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 5]],
            'allergens' => [],
            'packaging_cost' => 0,
        ]);
        $dailyMeal = DailyMeal::factory()
            ->for(Week::factory()->for($congregation))
            ->for($congregation)
            ->for($mainMenu)
            ->create(['meal_date' => '2026-12-12', 'estimated_people' => 10]);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $this->actingAs($coordinator)
            ->get(route('daily-meal-preparation-sheets.show', $dailyMeal))
            ->assertOk()
            ->assertSee('fără ambalaj')
            ->assertDontSee('caserole')
            ->assertDontSee('cost 0.00');
    }

    public function test_the_sheet_shows_the_roles_with_phones_and_the_delivery_time(): void
    {
        $congregation = Congregation::factory()->create();
        $mainMenu = Menu::factory()->create([
            'name' => 'Sarmale',
            'ingredients' => [['name' => 'Varza', 'quantity_per_person' => 0.2, 'unit' => 'kg', 'estimated_unit_cost' => 4]],
            'allergens' => [],
            'packaging_cost' => 1,
        ]);
        $dailyMeal = DailyMeal::factory()
            ->for(Week::factory()->for($congregation))
            ->for($congregation)
            ->for($mainMenu)
            ->create(['meal_date' => '2026-12-13', 'estimated_people' => 25, 'delivery_time' => '11:30']);
        Volunteer::factory()->for($dailyMeal)->create(['name' => 'Ana Popescu', 'phone' => '0712345678', 'role' => 'Gatit']);
        Volunteer::factory()->for($dailyMeal)->create(['name' => 'Ion Marin', 'phone' => '0723456789', 'role' => 'Aprovizionare si transport']);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $this->actingAs($coordinator)
            ->get(route('daily-meal-preparation-sheets.show', $dailyMeal))
            ->assertOk()
            ->assertSee('Echipe pe roluri și livrare')
            ->assertSee('Gătit')
            ->assertSee('Aprovizionare și transport')
            ->assertSee('Ana Popescu')
            ->assertSee('0712345678')
            ->assertSee('Ion Marin')
            ->assertSee('0723456789')
            ->assertSee('Ora de livrare pe șantier')
            ->assertSee('11:30');
    }
}
