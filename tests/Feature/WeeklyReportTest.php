<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\Week;
use App\Services\WeeklyShoppingListCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_calculator_aggregates_all_daily_meal_requirements(): void
    {
        $week = Week::factory()->create();
        $menu = Menu::factory()->create([
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 5]],
            'packaging_cost' => 1.5,
        ]);
        $secondMenu = Menu::factory()->create([
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 5]],
            'packaging_cost' => 1.5,
        ]);
        DailyMeal::factory()->for($week)->for($menu)->create(['estimated_people' => 30]);
        DailyMeal::factory()->for($week)->for($secondMenu)->create(['estimated_people' => 15]);

        $report = app(WeeklyShoppingListCalculator::class)->calculate($week);

        $this->assertSame([[
            'name' => 'Orez',
            'quantity' => 4.5,
            'unit' => 'kg',
            'estimated_unit_cost' => 5.0,
            'estimated_cost' => 22.5,
            'has_missing_price' => false,
            'needs_configuration' => false,
            'to_buy_quantity' => 4.5,
        ]], $report['ingredients']);
        $this->assertSame(['count' => 45, 'total_cost' => 67.5], $report['packaging']);
        $this->assertSame(['ingredients_cost' => 22.5, 'total_cost' => 90.0, 'has_missing_prices' => false], $report['totals']);
    }

    public function test_weekly_report_shows_the_meal_plan_only_for_an_authorized_user(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $meal = DailyMeal::factory()->for($week)->for($congregation)->create();
        Volunteer::factory()->for($meal)->create(['has_allergies' => true, 'allergy_details' => 'Fara gluten']);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $this->actingAs($coordinator)
            ->get(route('weekly-reports.show', $week))
            ->assertOk()
            ->assertSee('Lista de cumparaturi bruta')
            ->assertSee('Planificarea meselor')
            ->assertSee($congregation->name)
            ->assertDontSee('Fara gluten');

        $this->get(route('weekly-reports.show', Week::factory()->create()))->assertForbidden();
    }

    public function test_weekly_calculator_handles_multiple_zero_portion_days_without_dividing_by_zero(): void
    {
        $week = Week::factory()->create();
        $firstMenu = Menu::factory()->create([
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 8.5]],
        ]);
        $secondMenu = Menu::factory()->create([
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.08, 'unit' => 'kg', 'estimated_unit_cost' => 8.5]],
        ]);
        DailyMeal::factory()->for($week)->for($firstMenu)->create(['estimated_people' => 0]);
        DailyMeal::factory()->for($week)->for($secondMenu)->create(['estimated_people' => 0]);

        $report = app(WeeklyShoppingListCalculator::class)->calculate($week);

        $this->assertSame(0.0, $report['ingredients'][0]['quantity']);
        $this->assertSame(8.5, $report['ingredients'][0]['estimated_unit_cost']);
        $this->assertSame(0.0, $report['totals']['total_cost']);
    }

    public function test_weekly_calculator_includes_the_selected_dessert_in_quantities_and_costs(): void
    {
        $week = Week::factory()->create();
        $mainMenu = Menu::factory()->create([
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 5]],
            'packaging_cost' => 1,
        ]);
        $dessertMenu = Menu::factory()->create([
            'type' => 'dessert',
            'ingredients' => [['name' => 'Napolitana', 'quantity_per_person' => 1, 'unit' => 'buc', 'estimated_unit_cost' => 2]],
            'packaging_cost' => 0,
        ]);
        DailyMeal::factory()->for($week)->for($mainMenu)->create(['estimated_people' => 10, 'dessert_menu_id' => $dessertMenu->id]);

        $report = app(WeeklyShoppingListCalculator::class)->calculate($week);

        $this->assertSame(1.0, collect($report['ingredients'])->firstWhere('name', 'Orez')['quantity']);
        $this->assertSame(10.0, collect($report['ingredients'])->firstWhere('name', 'Napolitana')['quantity']);
        $this->assertSame(35.0, $report['totals']['total_cost']);
    }

    public function test_weekly_calculator_rounds_quantities_up_to_practical_shopping_units(): void
    {
        $week = Week::factory()->create();
        $menu = Menu::factory()->create([
            'ingredients' => [
                ['name' => 'Oua', 'quantity_per_person' => 2, 'unit' => 'buc', 'estimated_unit_cost' => 1],
                ['name' => 'Ulei', 'quantity_per_person' => 0.04, 'unit' => 'l', 'estimated_unit_cost' => 1],
                ['name' => 'Cartofi', 'quantity_per_person' => 0.06, 'unit' => 'kg', 'estimated_unit_cost' => 1],
            ],
        ]);
        DailyMeal::factory()->for($week)->for($menu)->create(['estimated_people' => 10]);

        $report = app(WeeklyShoppingListCalculator::class)->calculate($week);

        $this->assertSame(20.0, collect($report['ingredients'])->firstWhere('name', 'Oua')['to_buy_quantity']);
        $this->assertSame(0.5, collect($report['ingredients'])->firstWhere('name', 'Ulei')['to_buy_quantity']);
        $this->assertSame(1.0, collect($report['ingredients'])->firstWhere('name', 'Cartofi')['to_buy_quantity']);
    }

    public function test_weekly_report_counts_days_whose_menu_has_no_ingredients_or_cost(): void
    {
        $week = Week::factory()->create();
        $pricedMenu = Menu::factory()->create([
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 5]],
        ]);
        $emptyMenu = Menu::factory()->create(['ingredients' => []]);
        DailyMeal::factory()->for($week)->for($pricedMenu)->create(['estimated_people' => 20]);
        DailyMeal::factory()->for($week)->for($emptyMenu)->create(['estimated_people' => 20]);
        DailyMeal::factory()->for($week)->create(['menu_id' => null, 'estimated_people' => 20]);
        $administrator = User::factory()->create(['role' => 'admin']);

        $report = app(WeeklyShoppingListCalculator::class)->calculate($week);

        $this->assertSame(2, $report['incomplete_meals']);

        $this->actingAs($administrator)
            ->get(route('weekly-reports.show', $week))
            ->assertOk()
            ->assertSee('2 zile au meniu fara ingrediente sau cost configurat.')
            ->assertDontSee('nu au meniu ales si nu sunt incluse in total');
    }

    public function test_weekly_report_includes_a_printable_sheet_for_each_day_with_portions_and_costs(): void
    {
        $congregation = Congregation::factory()->create(['name' => 'Congregatia Est']);
        $week = Week::factory()->for($congregation)->create();
        $mainMenu = Menu::factory()->create([
            'name' => 'Mancare de cartofi',
            'ingredients' => [['name' => 'Cartofi', 'quantity_per_person' => 0.2, 'unit' => 'kg', 'estimated_unit_cost' => 5]],
            'allergens' => ['Telina'],
            'packaging_cost' => 1,
        ]);
        $soupMenu = Menu::factory()->create([
            'name' => 'Ciorba de legume',
            'type' => 'soup',
            'ingredients' => [['name' => 'Morcovi', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 1]],
            'packaging_cost' => 0,
        ]);
        DailyMeal::factory()->for($week)->for($congregation)->for($mainMenu)->create([
            'meal_date' => '2026-12-09',
            'estimated_people' => 30,
            'confirmed_people' => 40,
            'soup_menu_id' => $soupMenu->id,
        ]);
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)
            ->get(route('weekly-reports.show', $week))
            ->assertOk()
            ->assertSee('class="day-sheet"', false)
            ->assertSee('Mancare de cartofi')
            ->assertSee('Ciorba de legume')
            ->assertSee('Fel principal: Mancare de cartofi')
            ->assertSee('Ciorbă: Ciorba de legume')
            ->assertSee('Portii confirmate:')
            ->assertSee('Telina')
            ->assertSee('De cumparat')
            ->assertSee('2.10 RON / portie')
            ->assertSee('Media saptamanii:');
    }

    public function test_weekly_report_falls_back_to_estimated_portions_when_none_is_confirmed(): void
    {
        $week = Week::factory()->create();
        $menu = Menu::factory()->create([
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 5]],
            'packaging_cost' => 0,
        ]);
        DailyMeal::factory()->for($week)->for($menu)->create([
            'meal_date' => '2026-12-09',
            'estimated_people' => 10,
            'confirmed_people' => null,
        ]);
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)
            ->get(route('weekly-reports.show', $week))
            ->assertOk()
            ->assertSee('Portii estimate:')
            ->assertDontSee('Portii confirmate:')
            ->assertSee('0.50 RON / portie');
    }

    public function test_congregation_report_contains_only_its_days_and_assistant_contact(): void
    {
        $firstCongregation = Congregation::factory()->create([
            'name' => 'Congregatia Est',
            'assistant_name' => 'Ion Popescu',
            'assistant_phone' => '0712345678',
        ]);
        $secondCongregation = Congregation::factory()->create(['name' => 'Congregatia Vest']);
        $week = Week::factory()->for($firstCongregation)->create();
        $firstMenu = Menu::factory()->create(['name' => 'Meniu Est', 'allergens' => ['Gluten']]);
        $secondMenu = Menu::factory()->create(['name' => 'Meniu Vest']);
        DailyMeal::factory()->for($week)->for($firstCongregation)->for($firstMenu)->create(['estimated_people' => 30]);
        DailyMeal::factory()->for($week)->for($secondCongregation)->for($secondMenu)->create(['estimated_people' => 45]);
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)
            ->get(route('congregation-weekly-reports.show', [$week, $firstCongregation]))
            ->assertOk()
            ->assertSee('Congregatia Est')
            ->assertSee('Ion Popescu')
            ->assertSee('0712345678')
            ->assertSee('Meniu Est')
            ->assertSee('Alergeni: Gluten')
            ->assertSee('Siguranta alimentara')
            ->assertDontSee('Meniu Vest');
    }
}
