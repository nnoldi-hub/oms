<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Filament\Widgets\WeeklyScheduleSummary;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\User;
use App\Models\Week;
use App\Services\WeekScheduleSummaryService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WeekScheduleSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_uses_scheduled_days_and_excludes_free_days(): void
    {
        $cooks = Congregation::factory()->create(['name' => 'Bucuresti Est']);

        $monFri = Week::factory()->for($cooks)->create(['week_number' => 1, 'start_date' => '2026-11-30']);
        $monFriMeals = collect();
        foreach (['2026-11-30', '2026-12-01', '2026-12-02', '2026-12-03', '2026-12-04'] as $date) {
            $monFriMeals->push(DailyMeal::factory()->for($monFri)->for($cooks)->create(['meal_date' => $date]));
        }

        [$berceni, $romani, $uverturii] = Congregation::factory()
            ->count(3)
            ->sequence(
                fn () => ['name' => 'Bucuresti Berceni'],
                fn () => ['name' => 'Bucuresti Noi Rromani'],
                fn () => ['name' => 'Bucuresti Uverturii'],
            )
            ->create();
        $monFriMeals[0]->syncContributingCongregations([$berceni->id, $romani->id, $uverturii->id]);
        $monFriMeals[1]->syncContributingCongregations([$berceni->id, $romani->id, $uverturii->id]);

        $monSat = Week::factory()->for($cooks)->create(['week_number' => 2, 'start_date' => '2026-12-07']);
        foreach (['2026-12-07', '2026-12-08', '2026-12-09', '2026-12-10', '2026-12-11', '2026-12-12'] as $date) {
            DailyMeal::factory()->for($monSat)->for($cooks)->create(['meal_date' => $date]);
        }

        $tueFri = Week::factory()->for($cooks)->create(['week_number' => 3, 'start_date' => '2026-12-14']);
        foreach (['2026-12-15', '2026-12-16', '2026-12-17', '2026-12-18'] as $date) {
            DailyMeal::factory()->for($tueFri)->for($cooks)->create(['meal_date' => $date]);
        }

        $withFreeDay = Week::factory()->for($cooks)->create(['week_number' => 4, 'start_date' => '2026-12-21']);
        foreach (['2026-12-21', '2026-12-22', '2026-12-24', '2026-12-25'] as $date) {
            DailyMeal::factory()->for($withFreeDay)->for($cooks)->create(['meal_date' => $date]);
        }
        DailyMeal::factory()->for($withFreeDay)->for($cooks)->create([
            'meal_date' => '2026-12-23',
            'is_free_day' => true,
        ]);

        $summaries = app(WeekScheduleSummaryService::class)->summaries()->keyBy('week_number');

        $this->assertSame('luni–vineri', $summaries[1]['working_days']);
        $this->assertSame('luni–sâmbătă', $summaries[2]['working_days']);
        $this->assertSame('marți–vineri', $summaries[3]['working_days']);
        $this->assertSame('luni–marți, joi–vineri', $summaries[4]['working_days']);
        $this->assertSame(4, $summaries[4]['days_count']);
        $this->assertSame('Bucuresti Est', $summaries[1]['cooking']);
        $this->assertSame(
            'Bucuresti Berceni, Bucuresti Noi Rromani, Bucuresti Uverturii',
            $summaries[1]['contributors'],
        );
        $this->assertSame(3, $summaries[1]['contributors_count']);
        $this->assertCount(5, $summaries[1]['contributors_by_day']);
        $this->assertSame(
            ['Bucuresti Berceni', 'Bucuresti Noi Rromani', 'Bucuresti Uverturii'],
            $summaries[1]['contributors_by_day'][0]['names'],
        );
        $this->assertSame([], $summaries[1]['contributors_by_day'][2]['names']);
        $this->assertSame('Nealocata', $summaries[2]['contributors']);
    }

    public function test_final_week_lists_all_cooking_congregations(): void
    {
        $est = Congregation::factory()->create(['name' => 'Bucuresti Est']);
        $titan = Congregation::factory()->create(['name' => 'Bucuresti Titan']);
        $baltaAlba = Congregation::factory()->create(['name' => 'Bucuresti Balta Alba']);

        $week = Week::factory()->for($est)->create(['week_number' => 16, 'start_date' => '2027-03-22']);
        $dates = ['2027-03-22', '2027-03-23', '2027-03-24', '2027-03-25', '2027-03-26'];

        foreach ([$est, $est, $titan, $titan, $baltaAlba] as $index => $congregation) {
            DailyMeal::factory()->for($week)->for($congregation)->create([
                'meal_date' => $dates[$index],
            ]);
        }

        $summary = app(WeekScheduleSummaryService::class)->summaries()->firstWhere('week_number', 16);

        $this->assertSame(
            'Bucuresti Est, Bucuresti Titan, Bucuresti Balta Alba',
            $summary['cooking'],
        );
    }

    public function test_dashboard_widget_is_visible_to_cooking_coordinators_and_hidden_from_contributors(): void
    {
        $est = Congregation::factory()->create(['name' => 'Bucuresti Est']);
        $titan = Congregation::factory()->create(['name' => 'Bucuresti Titan']);
        $contributor = Congregation::factory()->create(['name' => 'Bucuresti Berceni']);

        $estWeek = Week::factory()->for($est)->create(['week_number' => 1, 'start_date' => '2026-11-30']);
        Week::factory()->for($titan)->create(['week_number' => 2, 'start_date' => '2026-12-07']);

        $estMeal = DailyMeal::factory()->for($estWeek)->for($est)->create(['meal_date' => '2026-11-30']);
        $estMeal->syncContributingCongregations([$contributor->id]);

        $cookingCoordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $est->id,
        ]);
        $contributorCoordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $contributor->id,
        ]);

        $this->actingAs($cookingCoordinator);
        $this->assertTrue(app(WeekScheduleSummaryService::class)->canView());

        $this->get('/admin')
            ->assertOk()
            ->assertSee('weekly-schedule-summary');

        Livewire::test(WeeklyScheduleSummary::class)
            ->assertSee('Rezumat program')
            ->assertSee('Săptămâna 1')
            ->assertSee('Bucuresti Est')
            ->assertSee('Bucuresti Titan')
            ->assertSee('Contribuie')
            ->assertSee('Bucuresti Berceni');

        $this->actingAs($contributorCoordinator);
        $this->assertFalse(app(WeekScheduleSummaryService::class)->canView());

        $this->get('/admin')
            ->assertOk()
            ->assertDontSee('weekly-schedule-summary');
    }

    public function test_calendar_limits_the_summary_to_the_current_and_next_week(): void
    {
        $cooks = Congregation::factory()->create(['name' => 'Bucuresti Est']);

        foreach ([
            [1, '2026-11-30'],
            [2, '2026-12-07'],
            [3, '2026-12-14'],
        ] as [$number, $date]) {
            $week = Week::factory()->for($cooks)->create([
                'week_number' => $number,
                'start_date' => $date,
            ]);
            foreach (range(0, 4) as $offset) {
                DailyMeal::factory()->for($week)->for($cooks)->create([
                    'meal_date' => CarbonImmutable::parse($date)->addDays($offset)->toDateString(),
                ]);
            }
        }

        $service = app(WeekScheduleSummaryService::class);

        $this->assertSame(
            [1, 2],
            $service->upcoming(2, CarbonImmutable::parse('2026-11-23'))->pluck('week_number')->all(),
        );
        $this->assertSame(
            [2, 3],
            $service->upcoming(2, CarbonImmutable::parse('2026-12-08'))->pluck('week_number')->all(),
        );
        $this->assertSame(
            [2, 3],
            $service->upcoming(2, CarbonImmutable::parse('2027-01-15'))->pluck('week_number')->all(),
        );

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-23'));

        try {
            $coordinator = User::factory()->create([
                'role' => 'coordinator',
                'congregation_id' => $cooks->id,
            ]);

            Livewire::actingAs($coordinator)
                ->test(CalendarSaptamanal::class)
                ->assertSee('Săptămâna în curs și următoarea')
                ->assertSee('Săptămâna 1')
                ->assertSee('Săptămâna 2')
                ->assertDontSee('Săptămâna 3');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_calendar_shows_summary_only_to_cooking_congregations(): void
    {
        $est = Congregation::factory()->create(['name' => 'Bucuresti Est']);
        $contributor = Congregation::factory()->create(['name' => 'Bucuresti Berceni']);

        $week = Week::factory()->for($est)->create(['week_number' => 1, 'start_date' => '2026-11-30']);
        $meal = DailyMeal::factory()->for($week)->for($est)->create(['meal_date' => '2026-11-30']);
        $meal->syncContributingCongregations([$contributor->id]);

        $cookingCoordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $est->id,
        ]);
        $contributorCoordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $contributor->id,
        ]);

        Livewire::actingAs($cookingCoordinator)
            ->test(CalendarSaptamanal::class)
            ->assertSee('Rezumat program')
            ->assertSee('Săptămâna 1');

        Livewire::actingAs($contributorCoordinator)
            ->test(CalendarSaptamanal::class)
            ->assertDontSee('Rezumat program');
    }
}
