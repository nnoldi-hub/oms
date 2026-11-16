<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Filament\Pages\CereriSchimbareSaptamana;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\User;
use App\Models\Week;
use App\Models\WeekChangeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class WeekChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_coordinator_can_request_a_week_change_and_admins_are_notified(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $congregation = Congregation::factory()->create();
        $target = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['week_number' => 7]);
        DailyMeal::factory()->for($week)->for($congregation)->create(['meal_date' => '2026-12-07']);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openWeekChangeRequest')
            ->set('weekChangeRequestReason', 'Avem nevoie de schimb din motive de program.')
            ->set('weekChangeRequestPreferredCongregationId', $target->id)
            ->call('submitWeekChangeRequest')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('week_change_requests', [
            'week_id' => $week->id,
            'congregation_id' => $congregation->id,
            'user_id' => $coordinator->id,
            'preferred_congregation_id' => $target->id,
            'status' => WeekChangeRequest::STATUS_PENDING,
        ]);

        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $admin->id)->count());
    }

    public function test_an_administrator_approving_a_request_moves_the_week_and_notifies_the_coordinator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $from = Congregation::factory()->create();
        $to = Congregation::factory()->create();
        $week = Week::factory()->for($from)->create();
        $meal = DailyMeal::factory()->for($week)->for($from)->create(['meal_date' => '2026-12-07']);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $from->id]);

        $request = WeekChangeRequest::create([
            'week_id' => $week->id,
            'congregation_id' => $from->id,
            'user_id' => $coordinator->id,
            'reason' => 'Motiv intemeiat.',
            'preferred_congregation_id' => $to->id,
            'status' => WeekChangeRequest::STATUS_PENDING,
        ]);

        Livewire::actingAs($admin)
            ->test(CereriSchimbareSaptamana::class)
            ->set('resolutionNote', 'Aprobat, multumim.')
            ->call('approve', $request->id)
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertSame(WeekChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertSame($admin->id, $request->resolved_by);
        $this->assertNotNull($request->resolved_at);

        $this->assertSame($to->id, $week->fresh()->congregation_id);
        $this->assertSame($to->id, $meal->fresh()->congregation_id);

        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $coordinator->id)->count());
    }

    public function test_an_administrator_can_reject_a_request_with_a_note(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $request = WeekChangeRequest::create([
            'week_id' => $week->id,
            'congregation_id' => $congregation->id,
            'user_id' => $coordinator->id,
            'reason' => 'Motiv oarecare.',
            'status' => WeekChangeRequest::STATUS_PENDING,
        ]);

        Livewire::actingAs($admin)
            ->test(CereriSchimbareSaptamana::class)
            ->call('openReject', $request->id)
            ->set('resolutionNote', 'Nu se poate in aceasta perioada.')
            ->call('reject')
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertSame(WeekChangeRequest::STATUS_REJECTED, $request->status);
        $this->assertSame('Nu se poate in aceasta perioada.', $request->resolution_note);

        $this->assertSame($congregation->id, $week->fresh()->congregation_id);
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $coordinator->id)->count());
    }

    public function test_a_coordinator_only_sees_the_requests_of_their_congregation(): void
    {
        $congregation = Congregation::factory()->create();
        $other = Congregation::factory()->create();
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);
        $otherCoordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $other->id]);

        $ownRequest = WeekChangeRequest::create([
            'week_id' => Week::factory()->for($congregation)->create()->id,
            'congregation_id' => $congregation->id,
            'user_id' => $coordinator->id,
            'reason' => 'Cererea mea.',
            'status' => WeekChangeRequest::STATUS_PENDING,
        ]);

        $otherRequest = WeekChangeRequest::create([
            'week_id' => Week::factory()->for($other)->create()->id,
            'congregation_id' => $other->id,
            'user_id' => $otherCoordinator->id,
            'reason' => 'Cererea altei congregatii.',
            'status' => WeekChangeRequest::STATUS_PENDING,
        ]);

        Livewire::actingAs($coordinator)
            ->test(CereriSchimbareSaptamana::class)
            ->assertSee('Cererea mea.')
            ->assertDontSee('Cererea altei congregatii.');

        $this->assertTrue($ownRequest->exists);
        $this->assertTrue($otherRequest->exists);
    }

    public function test_a_coordinator_cannot_approve_a_request(): void
    {
        $congregation = Congregation::factory()->create();
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);
        $week = Week::factory()->for($congregation)->create();
        $request = WeekChangeRequest::create([
            'week_id' => $week->id,
            'congregation_id' => $congregation->id,
            'user_id' => $coordinator->id,
            'reason' => 'Motiv.',
            'status' => WeekChangeRequest::STATUS_PENDING,
        ]);

        Livewire::actingAs($coordinator)
            ->test(CereriSchimbareSaptamana::class)
            ->call('approve', $request->id);

        $this->assertSame(WeekChangeRequest::STATUS_PENDING, $request->fresh()->status);
    }
}
