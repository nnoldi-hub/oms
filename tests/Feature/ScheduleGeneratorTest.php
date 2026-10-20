<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Week;
use App\Services\ScheduleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ScheduleGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_weekly_rotation_and_splits_the_last_week_two_two_one(): void
    {
        $congregations = Congregation::factory()->count(3)->create();

        app(ScheduleGenerator::class)->generate('2027-05-31', 4, $congregations->pluck('id')->all());

        $this->assertDatabaseCount('weeks', 4);
        $this->assertDatabaseCount('daily_meals', 20);
        $this->assertSame('2027-05-31', Week::where('week_number', 1)->value('start_date')->toDateString());
        $this->assertSame([
            $congregations[0]->id,
            $congregations[0]->id,
            $congregations[1]->id,
            $congregations[1]->id,
            $congregations[2]->id,
        ], DailyMeal::where('week_id', Week::where('week_number', 4)->value('id'))->orderBy('meal_date')->pluck('congregation_id')->all());
    }

    public function test_it_starts_on_any_weekday_and_skips_the_weekend(): void
    {
        $congregations = Congregation::factory()->count(3)->create();

        app(ScheduleGenerator::class)->generate('2026-11-27', 2, $congregations->pluck('id')->all());

        $dates = DailyMeal::query()->orderBy('meal_date')->pluck('meal_date')
            ->map(fn ($date) => $date->toDateString())
            ->all();

        $this->assertSame([
            '2026-11-27',
            '2026-11-30',
            '2026-12-01',
            '2026-12-02',
            '2026-12-03',
            '2026-12-04',
            '2026-12-07',
            '2026-12-08',
            '2026-12-09',
            '2026-12-10',
        ], $dates);

        $this->assertFalse(Week::where('week_number', 1)->value('include_saturday'));
    }

    public function test_it_can_include_saturday_as_a_working_day(): void
    {
        $congregations = Congregation::factory()->count(3)->create();

        app(ScheduleGenerator::class)->generate('2027-06-01', 1, $congregations->pluck('id')->all(), includeSaturday: true);

        $this->assertDatabaseCount('daily_meals', 5);
        $this->assertTrue(Week::where('week_number', 1)->value('include_saturday'));
        $this->assertSame(
            ['2027-06-01', '2027-06-02', '2027-06-03', '2027-06-04', '2027-06-05'],
            DailyMeal::query()->orderBy('meal_date')->pluck('meal_date')->map(fn ($date) => $date->toDateString())->all(),
        );
    }

    public function test_it_rejects_a_start_date_that_falls_on_a_weekend(): void
    {
        $congregations = Congregation::factory()->count(3)->create();

        $this->expectException(ValidationException::class);

        app(ScheduleGenerator::class)->generate('2027-06-05', 4, $congregations->pluck('id')->all());
    }

    public function test_it_rejects_a_date_range_that_overlaps_existing_meals(): void
    {
        $congregations = Congregation::factory()->count(3)->create();
        DailyMeal::factory()->create(['meal_date' => '2027-06-02']);

        $this->expectException(ValidationException::class);

        app(ScheduleGenerator::class)->generate('2027-05-31', 2, $congregations->pluck('id')->all());
    }
}