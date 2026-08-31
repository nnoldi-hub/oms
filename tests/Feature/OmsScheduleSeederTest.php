<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Week;
use App\Services\MealRequirementCalculator;
use Database\Seeders\OmsScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OmsScheduleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_full_schedule_and_splits_the_final_week_two_two_one(): void
    {
        $this->seed(OmsScheduleSeeder::class);

        $this->assertDatabaseCount('congregations', 3);
        $this->assertDatabaseCount('menus', 16);
        $this->assertDatabaseCount('weeks', 16);
        $this->assertDatabaseCount('daily_meals', 80);
        $this->assertSame('2026-11-28', Week::findOrFail(1)->start_date->toDateString());

        $finalWeekAssignments = Week::where('week_number', 16)
            ->firstOrFail()
            ->dailyMeals()
            ->orderBy('meal_date')
            ->pluck('congregation_id')
            ->all();
        $congregations = Congregation::orderBy('id')->pluck('id')->all();

        $this->assertSame([
            $congregations[0],
            $congregations[0],
            $congregations[1],
            $congregations[1],
            $congregations[2],
        ], $finalWeekAssignments);

        $this->seed(OmsScheduleSeeder::class);

        $this->assertDatabaseCount('daily_meals', 80);
        $this->assertSame(80, DailyMeal::count());
        $this->assertSame(16, Menu::count());
        $this->assertSame(1, DailyMeal::where('week_id', 1)->whereNotNull('soup_menu_id')->count());

        $weeklySoups = collect(range(1, 4))->map(function (int $weekNumber): string {
            $week = Week::where('week_number', $weekNumber)->firstOrFail();
            $soupMenuId = DailyMeal::where('week_id', $week->id)->whereNotNull('soup_menu_id')->value('soup_menu_id');

            return Menu::findOrFail($soupMenuId)->name;
        })->all();

        $this->assertSame([
            'Ciorba de legume',
            'Ciorba de perisoare',
            'Ciorba a la grec',
            'Supa cu galuste',
        ], $weeklySoups);
    }

    public function test_menu_ten_calculates_the_documented_ingredients_for_fifty_people(): void
    {
        $this->seed(OmsScheduleSeeder::class);
        $menu = Menu::where('name', 'Iahnie de fasole cu afumatura si gogonele')->firstOrFail();
        $meal = DailyMeal::factory()->for($menu)->for(Week::factory()->create(['week_number' => 99]))->create([
            'estimated_people' => 50,
            'meal_date' => '2027-04-01',
        ]);

        $requirements = app(MealRequirementCalculator::class)->calculate($meal);

        $this->assertSame([
            ['name' => 'Fasole boabe uscata', 'quantity' => 4.5, 'unit' => 'kg', 'estimated_unit_cost' => null, 'estimated_cost' => 0.0, 'has_missing_price' => true],
            ['name' => 'Ciolan afumat dezosat', 'quantity' => 4.0, 'unit' => 'kg', 'estimated_unit_cost' => null, 'estimated_cost' => 0.0, 'has_missing_price' => true],
            ['name' => 'Gogonele', 'quantity' => 5.0, 'unit' => 'kg', 'estimated_unit_cost' => null, 'estimated_cost' => 0.0, 'has_missing_price' => true],
        ], $requirements['ingredients']);
    }
}