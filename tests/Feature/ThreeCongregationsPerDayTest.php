<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Filament\Pages\CereriSchimbareSaptamana;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\User;
use App\Models\Week;
use App\Models\WeekChangeRequest;
use App\Services\WeekChangeRequestService;
use App\Support\CongregationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ThreeCongregationsPerDayTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_day_can_store_three_ordered_contributing_congregations_without_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$cooks, $first, $second, $third] = Congregation::factory()->count(4)->create();
        $week = Week::factory()->create(['congregation_id' => $cooks->id]);
        $meal = DailyMeal::factory()->for($week)->create([
            'meal_date' => '2026-12-07',
            'congregation_id' => $cooks->id,
        ]);

        $meal->syncContributingCongregations([$cooks->id, $first->id, $first->id, $second->id, $third->id], $admin);

        $meal->refresh();

        $this->assertSame([$first->id, $second->id, $third->id], $meal->contributingCongregationIds());
        $this->assertSame($cooks->id, $meal->congregation_id);
        $this->assertFalse($meal->hasContributingCongregation($cooks->id));
        $this->assertSame($admin->id, $meal->updated_by);
        $this->assertSame($admin->id, $meal->lastEditor()->id);
        $this->assertSame([0, 1, 2], DB::table('congregation_daily_meal')
            ->where('daily_meal_id', $meal->id)
            ->orderBy('position')
            ->pluck('position')
            ->all());
    }

    public function test_a_cooking_coordinator_has_scoped_day_access_and_sees_the_global_summary(): void
    {
        [$mine, $second, $third, $other] = Congregation::factory()->count(4)->create();
        $coordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $mine->id,
        ]);
        $week = Week::factory()->create(['congregation_id' => $mine->id]);

        $shared = DailyMeal::factory()->for($week)->create([
            'meal_date' => '2026-12-07',
            'congregation_id' => $mine->id,
        ]);
        $shared->syncContributingCongregations([$second->id, $third->id]);

        $unrelated = DailyMeal::factory()->for($week)->create([
            'meal_date' => '2026-12-08',
            'congregation_id' => $other->id,
        ]);
        $unrelated->syncContributingCongregations([$other->id]);

        $this->assertTrue(Gate::forUser($coordinator)->allows('view', $shared));
        $this->assertFalse(Gate::forUser($coordinator)->allows('view', $unrelated));

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->assertSee($second->name)
            ->assertSee($third->name)
            // Rezumatul programului este global pentru congregatiile care gatesc.
            ->assertSee($other->name);
    }

    public function test_an_admin_can_save_day_contributors_and_is_warned_when_there_are_not_three(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$cooks, $first, $second, $third] = Congregation::factory()->count(4)->create();
        $week = Week::factory()->create(['congregation_id' => $cooks->id]);
        $meal = DailyMeal::factory()->for($week)->create([
            'meal_date' => '2026-12-07',
            'congregation_id' => $cooks->id,
        ]);

        Livewire::actingAs($admin)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->set('dayCongregations.'.$meal->id, [$cooks->id, $first->id, $first->id, $second->id])
            ->call('saveDayCongregations', $meal->id)
            ->assertHasNoErrors()
            ->assertNotified('Congregatiile au fost salvate.');

        $this->assertSame([$first->id, $second->id], $meal->fresh()->contributingCongregationIds());
        $this->assertSame($cooks->id, $meal->fresh()->congregation_id);

        Livewire::actingAs($admin)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->set('dayCongregations.'.$meal->id, [$cooks->id, $first->id, $second->id, $third->id])
            ->call('saveDayCongregations', $meal->id)
            ->assertNotified('Cele trei congregatii au fost salvate.');

        $this->assertSame([$first->id, $second->id, $third->id], $meal->fresh()->contributingCongregationIds());
    }

    public function test_a_day_change_request_replaces_a_contributing_congregation_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$cooks, $contributor, $second, $third, $replacement] = Congregation::factory()->count(5)->create();
        $coordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $contributor->id,
        ]);
        $week = Week::factory()->create(['congregation_id' => $cooks->id]);
        $meal = DailyMeal::factory()->for($week)->create([
            'meal_date' => '2026-12-07',
            'congregation_id' => $cooks->id,
        ]);
        $meal->syncContributingCongregations([$contributor->id, $second->id, $third->id]);

        $request = app(WeekChangeRequestService::class)->createDayRequest(
            $meal,
            $coordinator,
            'Congregatia nu poate participa in aceasta zi.',
            $contributor,
            $replacement,
        );

        $this->assertSame('replace', $request->change_type);
        $this->assertNotNull($request->daily_meal_id);

        Livewire::actingAs($admin)
            ->test(CereriSchimbareSaptamana::class)
            ->call('approve', $request->id)
            ->assertHasNoErrors();

        $this->assertSame(WeekChangeRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame([$replacement->id, $second->id, $third->id], $meal->fresh()->contributingCongregationIds());
        $this->assertSame($cooks->id, $meal->fresh()->congregation_id);
    }

    public function test_a_contributor_cannot_get_financial_fields_of_a_day(): void
    {
        [$cooks, $contributor] = Congregation::factory()->count(2)->create();
        $coordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $contributor->id,
        ]);
        $week = Week::factory()->create(['congregation_id' => $cooks->id]);
        $meal = DailyMeal::factory()->for($week)->create([
            'meal_date' => '2026-12-07',
            'congregation_id' => $cooks->id,
            'maximum_budget' => 500,
        ]);
        $meal->syncContributingCongregations([$contributor->id]);

        $this->assertTrue(CongregationAccess::canSeeMeal($coordinator, $meal));
        $this->assertFalse(CongregationAccess::canManageMeal($coordinator, $meal));
        $this->assertTrue(CongregationAccess::isContributorOnly($coordinator, $meal));
    }
}
