<?php

namespace Tests\Feature;

use App\Filament\Pages\CosturiBuget;
use App\Models\DailyMeal;
use App\Models\FoodFundTransaction;
use App\Models\Menu;
use App\Models\User;
use App\Models\Week;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CosturiBugetTest extends TestCase
{
    use RefreshDatabase;

    private function menu(): Menu
    {
        return Menu::factory()->create([
            'type' => 'main',
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 10]],
            'packaging_cost' => 0,
        ]);
    }

    public function test_the_budget_page_compares_estimated_and_real_week_costs(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-20'));

        $menu = $this->menu();

        $weekOne = Week::factory()->create(['week_number' => 1]);
        $weekOne->forceFill(['start_date' => '2026-11-30'])->save();

        $weekTwo = Week::factory()->create(['week_number' => 2]);
        $weekTwo->forceFill(['start_date' => '2026-12-14'])->save();

        DailyMeal::factory()->for($weekOne)->for($menu)->create(['meal_date' => '2026-11-30', 'estimated_people' => 10]);
        DailyMeal::factory()->for($weekTwo)->for($menu)->create(['meal_date' => '2026-12-14', 'estimated_people' => 20]);

        FoodFundTransaction::create([
            'type' => 'expense',
            'transaction_date' => '2026-12-01',
            'amount' => 8,
            'description' => 'Achizitie legume',
        ]);
        FoodFundTransaction::create([
            'type' => 'expense',
            'transaction_date' => '2026-12-15',
            'amount' => 25,
            'description' => 'Achizitie carne',
        ]);
        FoodFundTransaction::create([
            'type' => 'income',
            'transaction_date' => '2026-12-15',
            'amount' => 1000,
            'description' => 'Fonduri primite',
        ]);

        $administrator = User::factory()->create(['role' => 'admin']);

        $component = Livewire::actingAs($administrator)->test(CosturiBuget::class);

        $budget = $component->instance()->budget;

        $this->assertSame(2, $budget['total_weeks']);
        $this->assertSame(30.0, $budget['projected_total']);
        $this->assertSame(33.0, $budget['recorded_expenses']);

        $firstWeek = $budget['weeks'][0];
        $secondWeek = $budget['weeks'][1];

        $this->assertSame(10.0, $firstWeek['estimated']);
        $this->assertSame(8.0, $firstWeek['spent']);
        $this->assertSame(2.0, $firstWeek['difference']);
        $this->assertFalse($firstWeek['over_budget']);

        $this->assertSame(20.0, $secondWeek['estimated']);
        $this->assertSame(25.0, $secondWeek['spent']);
        $this->assertTrue($secondWeek['over_budget']);
        $this->assertSame(-5.0, $secondWeek['difference']);

        $this->assertSame(25.0, $component->instance()->spentThisWeek);
        $this->assertSame(15.0, $budget['completed_average']);

        CarbonImmutable::setTestNow();
    }

    public function test_a_week_in_progress_is_not_marked_as_closed(): void
    {
        $menu = $this->menu();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-02'));

        $week = Week::factory()->create(['week_number' => 1]);
        $week->forceFill(['start_date' => '2026-11-30'])->save();
        DailyMeal::factory()->for($week)->for($menu)->create(['meal_date' => '2026-11-30', 'estimated_people' => 10]);

        $component = Livewire::actingAs(User::factory()->create(['role' => 'admin']))->test(CosturiBuget::class);

        $this->assertFalse($component->instance()->budget['weeks'][0]['closed']);
        $this->assertSame(0, $component->instance()->budget['completed_weeks']);

        CarbonImmutable::setTestNow();
    }
}
