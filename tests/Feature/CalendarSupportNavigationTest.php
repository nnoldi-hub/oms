<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\User;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarSupportNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_keeps_only_the_cooking_congregation_sheet_and_has_no_public_per_congregation_links(): void
    {
        $cooks = Congregation::factory()->create(['name' => 'Bucuresti Est']);
        $first = Congregation::factory()->create(['name' => 'Contribuitor Alpha']);
        $second = Congregation::factory()->create(['name' => 'Contribuitor Beta']);
        $week = Week::factory()->for($cooks)->create(['week_number' => 52, 'start_date' => '2026-12-21']);
        $meal = DailyMeal::factory()->for($week)->for($cooks)->create(['meal_date' => '2026-12-21']);
        $meal->syncContributingCongregations([$first->id, $second->id]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->assertSee('Fisa congregatiei care gateste')
            ->assertDontSee('Fisa Contribuitor Alpha')
            ->assertDontSee('Fisa Contribuitor Beta')
            ->assertDontSee('Creeaza link public');

        $this->get('/fise/token-inexistent')->assertNotFound();
    }
}
