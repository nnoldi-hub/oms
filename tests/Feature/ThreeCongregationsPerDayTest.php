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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ThreeCongregationsPerDayTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_day_can_store_three_ordered_congregations_without_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$first, $second, $third] = Congregation::factory()->count(3)->create();
        $week = Week::factory()->create(['congregation_id' => null]);
        $meal = DailyMeal::factory()->for($week)->create([
            'meal_date' => '2026-12-07',
            'congregation_id' => $first->id,
        ]);

        $meal->syncCongregations([$first->id, $second->id, $first->id, $third->id], $admin);

        $meal->refresh();

        $this->assertSame([$first->id, $second->id, $third->id], $meal->congregationIds());
        $this->assertSame($first->id, $meal->congregation_id);
        $this->assertSame($admin->id, $meal->updated_by);
        $this->assertSame($admin->id, $meal->lastEditor()->id);
        $this->assertSame([0, 1, 2], DB::table('congregation_daily_meal')
            ->where('daily_meal_id', $meal->id)
            ->orderBy('position')
            ->pluck('position')
            ->all());
    }

    public function test_a_coordinator_sees_only_days_where_their_congregation_is_one_of_the_three(): void
    {
        [$mine, $second, $third, $other] = Congregation::factory()->count(4)->create();
        $coordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $mine->id,
        ]);
        $week = Week::factory()->create(['congregation_id' => null]);

        $shared = DailyMeal::factory()->for($week)->create([
            'meal_date' => '2026-12-07',
            'congregation_id' => $mine->id,
        ]);
        $shared->syncCongregations([$mine->id, $second->id, $third->id]);

        $unrelated = DailyMeal::factory()->for($week)->create([
            'meal_date' => '2026-12-08',
            'congregation_id' => $other->id,
        ]);
        $unrelated->syncCongregations([$other->id]);

        $this->assertTrue(Gate::forUser($coordinator)->allows('view', $shared));
        $this->assertFalse(Gate::forUser($coordinator)->allows('view', $unrelated));

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->assertSee($second->name)
            ->assertSee($third->name)
            ->assertDontSee($other->name);
    }

    public function test_an_admin_can_save_day_congregations_and_is_warned_when_there_are_not_three(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$first, $second, $third] = Congregation::factory()->count(3)->create();
        $week = Week::factory()->create(['congregation_id' => null]);
        $meal = DailyMeal::factory()->for($week)->create(['meal_date' => '2026-12-07']);

        Livewire::actingAs($admin)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->set('dayCongregations.'.$meal->id, [$first->id, $first->id, $second->id])
            ->call('saveDayCongregations', $meal->id)
            ->assertHasNoErrors()
            ->assertNotified('Congregatiile au fost salvate.');

        $this->assertSame([$first->id, $second->id], $meal->fresh()->congregationIds());

        Livewire::actingAs($admin)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->set('dayCongregations.'.$meal->id, [$first->id, $second->id, $third->id])
            ->call('saveDayCongregations', $meal->id)
            ->assertNotified('Cele trei congregatii au fost salvate.');

        $this->assertSame([$first->id, $second->id, $third->id], $meal->fresh()->congregationIds());
    }

    public function test_a_day_change_request_can_replace_one_of_the_three_congregations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$mine, $second, $third, $replacement] = Congregation::factory()->count(4)->create();
        $coordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $mine->id,
        ]);
        $week = Week::factory()->create(['congregation_id' => null]);
        $meal = DailyMeal::factory()->for($week)->create([
            'meal_date' => '2026-12-07',
            'congregation_id' => $mine->id,
        ]);
        $meal->syncCongregations([$mine->id, $second->id, $third->id]);

        $request = app(WeekChangeRequestService::class)->createDayRequest(
            $meal,
            $coordinator,
            'Congregatia nu poate participa in aceasta zi.',
            $mine,
            $replacement,
        );

        $this->assertSame('replace', $request->change_type);
        $this->assertNotNull($request->daily_meal_id);

        Livewire::actingAs($admin)
            ->test(CereriSchimbareSaptamana::class)
            ->call('approve', $request->id)
            ->assertHasNoErrors();

        $this->assertSame(WeekChangeRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame([$replacement->id, $second->id, $third->id], $meal->fresh()->congregationIds());
        $this->assertSame($replacement->id, $meal->fresh()->congregation_id);
    }
}
