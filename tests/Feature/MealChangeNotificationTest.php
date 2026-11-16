<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\MealChangeLog;
use App\Models\Menu;
use App\Models\User;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class MealChangeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_coordinator_changing_a_planned_dish_is_journaled_and_notifies_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $congregation = Congregation::factory()->create(['name' => 'Congregatia Sud']);
        $week = Week::factory()->for($congregation)->create();
        $planned = Menu::factory()->create(['name' => 'Planificat', 'type' => 'main']);
        $replacement = Menu::factory()->create(['name' => 'Inlocuitor', 'type' => 'main']);
        $meal = DailyMeal::factory()->for($week)->for($congregation)->create([
            'meal_date' => '2026-12-07',
            'menu_id' => $planned->id,
        ]);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->set("plannedMenus.{$meal->id}", $replacement->id)
            ->call('savePlannedMenu', $meal->id, 'main')
            ->assertHasNoErrors();

        $this->assertSame($replacement->id, $meal->fresh()->menu_id);

        $this->assertDatabaseHas('meal_change_logs', [
            'daily_meal_id' => $meal->id,
            'event_type' => MealChangeLog::EVENT_DISH_CHANGED,
            'old_value' => 'Planificat',
            'new_value' => 'Inlocuitor',
            'user_id' => $coordinator->id,
        ]);

        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $admin->id)->count());
    }

    public function test_changes_made_by_an_administrator_are_not_journaled_or_notified(): void
    {
        User::factory()->create(['role' => 'admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $planned = Menu::factory()->create(['type' => 'main']);
        $replacement = Menu::factory()->create(['type' => 'main']);
        $meal = DailyMeal::factory()->for($week)->for($congregation)->create([
            'meal_date' => '2026-12-08',
            'menu_id' => $planned->id,
        ]);

        Livewire::actingAs($admin)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->set("plannedMenus.{$meal->id}", $replacement->id)
            ->call('savePlannedMenu', $meal->id, 'main')
            ->assertHasNoErrors();

        $this->assertSame($replacement->id, $meal->fresh()->menu_id);
        $this->assertSame(0, MealChangeLog::query()->count());
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_a_coordinator_cooking_a_different_dish_than_planned_is_journaled(): void
    {
        User::factory()->create(['role' => 'admin']);
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $planned = Menu::factory()->create(['name' => 'Planificat', 'type' => 'main']);
        $cooked = Menu::factory()->create(['name' => 'Gatit real', 'type' => 'main']);
        $meal = DailyMeal::factory()->for($week)->for($congregation)->create([
            'meal_date' => '2026-12-09',
            'menu_id' => $planned->id,
        ]);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->set("cookedFlags.{$meal->id}", true)
            ->set("actualMenus.{$meal->id}", $cooked->id)
            ->call('markCooked', $meal->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('meal_change_logs', [
            'daily_meal_id' => $meal->id,
            'event_type' => MealChangeLog::EVENT_COOKED_DIFFERENT,
            'new_value' => 'Gatit real',
        ]);
    }

    public function test_a_coordinator_reporting_a_much_higher_actual_cost_is_journaled(): void
    {
        User::factory()->create(['role' => 'admin']);
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $menu = Menu::factory()->create([
            'type' => 'main',
            'ingredients' => [],
            'cost_per_portion' => 10,
            'packaging_cost' => 0,
        ]);
        $meal = DailyMeal::factory()->for($week)->for($congregation)->create([
            'meal_date' => '2026-12-10',
            'menu_id' => $menu->id,
            'estimated_people' => 10,
        ]);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->set("actualCosts.{$meal->id}", 500)
            ->call('markCooked', $meal->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('meal_change_logs', [
            'daily_meal_id' => $meal->id,
            'event_type' => MealChangeLog::EVENT_COST_DEVIATION,
        ]);
    }
}
