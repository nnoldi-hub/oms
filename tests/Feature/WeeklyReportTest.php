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