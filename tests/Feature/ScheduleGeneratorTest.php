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

        app(ScheduleGenerator::class)->generate('2027-06-01', 4, $congregations->pluck('id')->all());

        $this->assertDatabaseCount('weeks', 4);
        $this->assertDatabaseCount('daily_meals', 20);
        $this->assertSame('2027-06-01', Week::where('week_number', 1)->value('start_date')->toDateString());
        $this->assertSame([
            $congregations[0]->id,
            $congregations[0]->id,
            $congregations[1]->id,
            $congregations[1]->id,
            $congregations[2]->id,
        ], DailyMeal::where('week_id', Week::where('week_number', 4)->value('id'))->orderBy('meal_date')->pluck('congregation_id')->all());
    }

    public function test_it_rejects_a_date_range_that_overlaps_existing_meals(): void
    {
        $congregations = Congregation::factory()->count(3)->create();
        DailyMeal::factory()->create(['meal_date' => '2027-06-03']);

        $this->expectException(ValidationException::class);

        app(ScheduleGenerator::class)->generate('2027-06-01', 2, $congregations->pluck('id')->all());
    }
}