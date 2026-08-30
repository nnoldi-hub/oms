<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\User;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarSaptamanalTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_calendar_page_shows_the_days_of_an_authorized_week_as_cards(): void
    {
        $congregation = Congregation::factory()->create(['name' => 'Congregatia Nord']);
        $week = Week::factory()->for($congregation)->create(['week_number' => 3]);
        $menu = Menu::factory()->create(['name' => 'Meniu calendar']);
        $congregation->menus()->attach($menu);
        DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create([
            'meal_date' => '2026-12-07',
            'estimated_people' => 45,
        ]);
        $user = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $this->actingAs($user)
            ->get(CalendarSaptamanal::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSee('Calendar saptamanal')
            ->assertSee('Congregatia Nord')
            ->assertSee('Meniu calendar')
            ->assertSee('45');
    }

    public function test_an_administrator_can_update_portions_from_a_calendar_card(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $menu = Menu::factory()->create();
        $congregation->menus()->attach($menu);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create();
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->set("estimatedPeople.{$dailyMeal->id}", 55)
            ->call('saveEstimatedPeople', $dailyMeal->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('daily_meals', ['id' => $dailyMeal->id, 'estimated_people' => 55]);
    }
}