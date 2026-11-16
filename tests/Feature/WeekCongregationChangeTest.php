<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\DailyMealCostSnapshot;
use App\Models\FoodFundTransaction;
use App\Models\Menu;
use App\Models\PublicCongregationWeekLink;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\Week;
use App\Models\WeekChangeLog;
use App\Models\WorkTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WeekCongregationChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_move_a_week_to_another_congregation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $from = Congregation::factory()->create(['name' => 'Congregatia A']);
        $to = Congregation::factory()->create(['name' => 'Congregatia B']);
        $week = Week::factory()->for($from)->create(['week_number' => 5]);
        $menu = Menu::factory()->create();

        $meal = DailyMeal::factory()->for($week)->for($from)->for($menu)->create([
            'meal_date' => '2026-12-07',
        ]);

        Volunteer::factory()->create(['daily_meal_id' => $meal->id]);
        WorkTeam::create(['daily_meal_id' => $meal->id, 'name' => 'Echipa bucatarie']);
        $snapshot = DailyMealCostSnapshot::create([
            'daily_meal_id' => $meal->id,
            'congregation_id' => $from->id,
            'meal_date' => '2026-12-07',
            'portions' => 20,
            'ingredients_cost' => 100,
            'fixed_costs_cost' => 0,
            'packaging_cost' => 0,
            'total_cost' => 100,
            'cost_per_portion' => 5,
            'has_missing_prices' => false,
        ]);
        $link = PublicCongregationWeekLink::create(['week_id' => $week->id, 'congregation_id' => $from->id]);
        $transaction = FoodFundTransaction::create([
            'type' => 'income',
            'transaction_date' => '2026-12-07',
            'amount' => 200,
            'description' => 'Contributie initiala',
            'daily_meal_id' => $meal->id,
            'congregation_id' => $from->id,
        ]);

        Livewire::actingAs($admin)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openWeekChange', 'move')
            ->set('weekChangeTargetCongregationId', $to->id)
            ->set('weekChangeReason', 'Congregatia nu poate asigura saptamana.')
            ->call('applyWeekChange')
            ->assertHasNoErrors();

        $this->assertSame($to->id, $week->fresh()->congregation_id);
        $this->assertSame($to->id, $meal->fresh()->congregation_id);
        $this->assertSame(0, Volunteer::query()->where('daily_meal_id', $meal->id)->count());
        $this->assertSame(0, WorkTeam::query()->where('daily_meal_id', $meal->id)->count());
        $this->assertSame($to->id, $snapshot->fresh()->congregation_id);
        $this->assertSame($to->id, $link->fresh()->congregation_id);

        $transaction->refresh();
        $this->assertSame($to->id, $transaction->congregation_id);
        $this->assertStringContainsString('Congregatia responsabila a fost schimbata', (string) $transaction->description);

        $this->assertDatabaseHas('week_change_logs', [
            'type' => WeekChangeLog::TYPE_MOVE,
            'week_id' => $week->id,
            'from_congregation_id' => $from->id,
            'to_congregation_id' => $to->id,
            'user_id' => $admin->id,
            'reason' => 'Congregatia nu poate asigura saptamana.',
        ]);
    }

    public function test_moving_a_week_with_cooked_days_requires_extra_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $from = Congregation::factory()->create();
        $to = Congregation::factory()->create();
        $week = Week::factory()->for($from)->create();
        $meal = DailyMeal::factory()->for($week)->for($from)->create([
            'meal_date' => '2026-12-07',
            'actual_cost' => 150,
        ]);

        Livewire::actingAs($admin)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openWeekChange', 'move')
            ->set('weekChangeTargetCongregationId', $to->id)
            ->set('weekChangeReason', 'Motiv intemeiat.')
            ->call('applyWeekChange')
            ->assertHasErrors('weekChangeConfirmCooked');

        $this->assertSame($from->id, $week->fresh()->congregation_id);
        $this->assertSame(0, WeekChangeLog::query()->count());

        Livewire::actingAs($admin)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openWeekChange', 'move')
            ->set('weekChangeTargetCongregationId', $to->id)
            ->set('weekChangeReason', 'Motiv intemeiat.')
            ->set('weekChangeConfirmCooked', true)
            ->call('applyWeekChange')
            ->assertHasNoErrors();

        $this->assertSame($to->id, $week->fresh()->congregation_id);
        $this->assertSame($to->id, $meal->fresh()->congregation_id);
    }

    public function test_an_administrator_can_swap_the_congregations_of_two_weeks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $a = Congregation::factory()->create();
        $b = Congregation::factory()->create();
        $weekA = Week::factory()->for($a)->create(['week_number' => 1]);
        $weekB = Week::factory()->for($b)->create(['week_number' => 2]);
        $mealA = DailyMeal::factory()->for($weekA)->for($a)->create(['meal_date' => '2026-12-07']);
        $mealB = DailyMeal::factory()->for($weekB)->for($b)->create(['meal_date' => '2026-12-14']);

        Livewire::actingAs($admin)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $weekA->id)
            ->call('openWeekChange', 'swap')
            ->set('weekChangeSecondWeekId', $weekB->id)
            ->set('weekChangeReason', 'Interschimbare planificata.')
            ->call('applyWeekChange')
            ->assertHasNoErrors();

        $this->assertSame($b->id, $weekA->fresh()->congregation_id);
        $this->assertSame($a->id, $weekB->fresh()->congregation_id);
        $this->assertSame($b->id, $mealA->fresh()->congregation_id);
        $this->assertSame($a->id, $mealB->fresh()->congregation_id);

        $this->assertDatabaseHas('week_change_logs', [
            'type' => WeekChangeLog::TYPE_SWAP,
            'week_id' => $weekA->id,
            'second_week_id' => $weekB->id,
        ]);
    }

    public function test_a_coordinator_cannot_change_the_week_congregation(): void
    {
        $congregation = Congregation::factory()->create();
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);
        $week = Week::factory()->for($congregation)->create();

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openWeekChange', 'move')
            ->assertForbidden();
    }
}
