<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\DailySupplyPlan;
use App\Models\Menu;
use App\Models\SupplyContribution;
use App\Models\SupplyItem;
use App\Models\Week;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftWeekdaysCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_moves_a_saturday_based_schedule_to_monday_to_friday(): void
    {
        $congregation = Congregation::factory()->create();
        $menu = Menu::factory()->create(['type' => 'main']);

        foreach ([1 => '2026-11-28', 2 => '2026-12-05'] as $weekNumber => $start) {
            $week = Week::factory()->for($congregation)->create([
                'week_number' => $weekNumber,
                'start_date' => $start,
            ]);

            foreach (range(0, 4) as $dayIndex) {
                DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create([
                    'meal_date' => CarbonImmutable::parse($start)->addDays($dayIndex),
                ]);
            }
        }

        $firstMeal = DailyMeal::orderBy('meal_date')->firstOrFail();
        DailySupplyPlan::create([
            'plan_date' => $firstMeal->meal_date,
            'daily_meal_id' => $firstMeal->id,
            'people_count' => 30,
        ]);

        $item = SupplyItem::create(['name' => 'Apa plata 0.5L', 'category' => 'water', 'unit' => 'buc']);
        SupplyContribution::create([
            'congregation_id' => $congregation->id,
            'supply_item_id' => $item->id,
            'delivery_date' => '2026-12-01',
            'quantity' => 40,
        ]);
        SupplyContribution::create([
            'congregation_id' => $congregation->id,
            'supply_item_id' => $item->id,
            'delivery_date' => '2027-01-15',
            'quantity' => 10,
        ]);

        $this->artisan('oms:shift-weekdays', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('2026-11-28', $this->date(Week::where('week_number', 1)->value('start_date')));
        $this->assertSame('2026-11-28', $this->date(DailyMeal::orderBy('meal_date')->value('meal_date')));

        $this->artisan('oms:shift-weekdays')->assertSuccessful();

        $this->assertSame('2026-11-30', $this->date(Week::where('week_number', 1)->value('start_date')));
        $this->assertSame('2026-12-07', $this->date(Week::where('week_number', 2)->value('start_date')));

        $mealDates = DailyMeal::orderBy('meal_date')->pluck('meal_date')->map(fn ($date) => $this->date($date))->all();
        $this->assertSame('2026-11-30', $mealDates[0]);
        $this->assertSame('2026-12-11', end($mealDates));
        $this->assertCount(10, $mealDates);
        $this->assertSame(
            ['2026-11-30', '2026-12-01', '2026-12-02', '2026-12-03', '2026-12-04', '2026-12-07', '2026-12-08', '2026-12-09', '2026-12-10', '2026-12-11'],
            $mealDates,
        );
        $this->assertTrue(CarbonImmutable::parse($mealDates[0])->isMonday());

        $this->assertSame(
            '2026-11-30',
            $this->date(DailySupplyPlan::query()->value('plan_date')),
        );

        $this->assertSame([
            '2026-12-03',
            '2027-01-15',
        ], SupplyContribution::orderBy('delivery_date')->pluck('delivery_date')->map(fn ($date) => $this->date($date))->all());
    }

    public function test_it_reports_a_schedule_that_already_starts_on_a_monday(): void
    {
        Week::factory()->create(['start_date' => '2026-11-30']);

        $this->artisan('oms:shift-weekdays')
            ->expectsOutputToContain('deja intr-o zi de luni')
            ->assertSuccessful();
    }

    public function test_it_refuses_to_shift_when_the_weeks_do_not_share_the_same_start_day(): void
    {
        Week::factory()->create(['week_number' => 1, 'start_date' => '2026-11-28']);
        Week::factory()->create(['week_number' => 2, 'start_date' => '2026-12-06']);

        $this->artisan('oms:shift-weekdays')->assertFailed();

        $this->assertSame('2026-11-28', $this->date(Week::where('week_number', 1)->value('start_date')));
    }

    private function date(mixed $value): string
    {
        return CarbonImmutable::parse($value)->toDateString();
    }
}
