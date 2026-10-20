<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Week;
use App\Services\MealRequirementCalculator;
use Carbon\CarbonImmutable;
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
        $this->assertDatabaseCount('menus', 17);
        $this->assertDatabaseCount('weeks', 16);
        $this->assertDatabaseCount('daily_meals', 80);
        $this->assertSame('2026-11-30', CarbonImmutable::parse(Week::findOrFail(1)->start_date)->toDateString());

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
        $this->assertSame(17, Menu::count());
        $this->assertSame(2, DailyMeal::where('week_id', 1)->whereNotNull('soup_menu_id')->count());
    }

    public function test_every_week_starts_on_a_monday_and_the_work_days_are_monday_to_friday(): void
    {
        $this->seed(OmsScheduleSeeder::class);

        Week::query()->orderBy('week_number')->get()->each(function (Week $week): void {
            $start = CarbonImmutable::parse($week->start_date);

            $this->assertTrue($start->isMonday(), 'Saptamana '.$week->week_number.' nu incepe lunea.');

            $week->dailyMeals()->orderBy('meal_date')->get()
                ->values()
                ->each(function (DailyMeal $meal, int $index) use ($start): void {
                    $this->assertSame(
                        $start->addDays($index)->toDateString(),
                        CarbonImmutable::parse($meal->meal_date)->toDateString(),
                    );
                    $this->assertLessThanOrEqual(5, CarbonImmutable::parse($meal->meal_date)->isoWeekday());
                });
        });
    }

    public function test_every_week_has_exactly_two_different_soups_on_tuesday_and_thursday(): void
    {
        $this->seed(OmsScheduleSeeder::class);

        Week::query()->orderBy('week_number')->get()->each(function (Week $week): void {
            $soups = $week->dailyMeals()->whereNotNull('soup_menu_id')->orderBy('meal_date')->get();

            $this->assertCount(2, $soups);
            $this->assertSame([2, 4], $soups->map(
                fn (DailyMeal $meal) => CarbonImmutable::parse($meal->meal_date)->isoWeekday(),
            )->all());
            $this->assertNotSame($soups[0]->soup_menu_id, $soups[1]->soup_menu_id);
        });
    }

    public function test_soups_rotate_through_five_recipes(): void
    {
        $this->seed(OmsScheduleSeeder::class);

        $weeklySoupPairs = Week::query()->orderBy('week_number')->take(5)->get()
            ->map(fn (Week $week) => $week->dailyMeals()->whereNotNull('soup_menu_id')->orderBy('meal_date')->get()
                ->map(fn (DailyMeal $meal) => Menu::findOrFail($meal->soup_menu_id)->name)
                ->all())
            ->all();

        $this->assertSame([
            ['Ciorba de legume', 'Ciorba de perisoare'],
            ['Ciorba a la grec', 'Supa cu galuste'],
            ['Ciorba de fasole', 'Ciorba de legume'],
            ['Ciorba de perisoare', 'Ciorba a la grec'],
            ['Supa cu galuste', 'Ciorba de fasole'],
        ], $weeklySoupPairs);
    }

    public function test_main_dishes_are_never_reused_within_ten_days(): void
    {
        $this->seed(OmsScheduleSeeder::class);

        $smallestGaps = DailyMeal::query()
            ->whereNotNull('menu_id')
            ->orderBy('meal_date')
            ->get()
            ->groupBy('menu_id')
            ->map(fn ($meals) => $meals
                ->values()
                ->map(fn (DailyMeal $meal, int $index) => $index === 0
                    ? null
                    : (int) $meals[$index - 1]->meal_date->diffInDays($meal->meal_date, absolute: true))
                ->filter()
                ->values());

        $this->assertGreaterThanOrEqual(
            10,
            $smallestGaps->flatten()->min(),
            'Un fel principal a fost reutilizat la mai putin de 10 zile distanta.',
        );
    }

    public function test_the_seeder_refuses_to_run_over_a_schedule_that_does_not_start_on_a_monday(): void
    {
        $this->seed(OmsScheduleSeeder::class);
        Week::where('week_number', 1)->update(['start_date' => '2026-11-28']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('oms:shift-weekdays');

        $this->seed(OmsScheduleSeeder::class);
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
