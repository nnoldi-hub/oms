<?php

namespace Tests\Feature;

use App\Filament\Widgets\TomorrowMealOverview;
use App\Filament\Widgets\TomorrowMessageWidget;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\Week;
use App\Models\WorkTeam;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TomorrowMealOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_the_next_meal_with_portions_allergens_and_shopping_list(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-30 08:00'));

        $congregation = Congregation::factory()->create(['name' => 'Congregatia 2']);
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $main = Menu::factory()->create([
            'name' => 'Tocanita de pui',
            'type' => 'main',
            'allergens' => [],
            'ingredients' => [['name' => 'Pulpe de pui', 'quantity_per_person' => 0.15, 'unit' => 'kg']],
            'packaging_cost' => 1,
        ]);

        DailyMeal::factory()->for($week)->for($congregation)->for($main)->create([
            'meal_date' => '2026-12-01',
            'estimated_people' => 40,
            'status' => 'confirmed',
        ]);

        $administrator = User::factory()->create(['role' => 'admin']);

        $component = Livewire::actingAs($administrator)->test(TomorrowMealOverview::class);

        $component
            ->assertSee('Tocanita de pui')
            ->assertSee('40 persoane')
            ->assertSee('Congregatia 2')
            ->assertSee('Pulpe de pui 6 kg')
            ->assertSee('Niciunul')
            ->assertSee('rapoarte/zile/', escape: false)
            ->assertDontSee('Fara masa programata');

        CarbonImmutable::setTestNow();
    }

    public function test_it_warns_when_a_recipe_has_no_confirmed_allergens(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-30 08:00'));

        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $main = Menu::factory()->create(['name' => 'Meniu fara alergeni', 'type' => 'main', 'allergens' => null]);
        $soup = Menu::factory()->create(['name' => 'Ciorba de fasole', 'type' => 'soup', 'allergens' => null]);

        DailyMeal::factory()->for($week)->for($congregation)->for($main)->create([
            'meal_date' => '2026-12-01',
            'soup_menu_id' => $soup->id,
            'estimated_people' => 30,
            'status' => 'confirmed',
        ]);

        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(TomorrowMealOverview::class)
            ->assertSee('De confirmat');

        CarbonImmutable::setTestNow();
    }

    public function test_it_handles_a_day_without_a_scheduled_meal(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-30 08:00'));

        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(TomorrowMealOverview::class)
            ->assertSee('Fara masa programata');

        CarbonImmutable::setTestNow();
    }

    public function test_it_shows_tomorrows_teams_and_uncovered_roles(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-30 08:00'));

        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $main = Menu::factory()->create(['name' => 'Meniu echipe', 'type' => 'main']);
        $meal = DailyMeal::factory()->for($week)->for($congregation)->for($main)->create([
            'meal_date' => '2026-12-01',
            'estimated_people' => 20,
            'status' => 'confirmed',
        ]);
        WorkTeam::create(['daily_meal_id' => $meal->id, 'name' => 'Echipa masa pranz', 'starts_at' => '08:00', 'ends_at' => '12:00']);
        Volunteer::factory()->for($meal)->create(['role' => 'Gatit']);
        Volunteer::factory()->for($meal)->create(['role' => 'Aprovizionare si transport']);
        Volunteer::factory()->for($meal)->create(['role' => 'Impachetare si servire']);
        Volunteer::factory()->for($meal)->create(['role' => 'Donatii']);

        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(TomorrowMealOverview::class)
            ->assertSee('Echipe si roluri')
            ->assertSee('Echipa masa pranz (08:00-12:00)')
            ->assertSee('Toate rolurile au o persoana alocata');

        CarbonImmutable::setTestNow();
    }

    public function test_it_flags_uncovered_roles_when_some_are_missing(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-30 08:00'));

        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $main = Menu::factory()->create(['name' => 'Meniu echipe', 'type' => 'main']);
        $meal = DailyMeal::factory()->for($week)->for($congregation)->for($main)->create([
            'meal_date' => '2026-12-01',
            'estimated_people' => 20,
            'status' => 'confirmed',
        ]);
        Volunteer::factory()->for($meal)->create(['role' => 'Gatit']);

        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(TomorrowMealOverview::class)
            ->assertSee('Neacoperite: Aprovizionare si transport, Impachetare si servire, Donatii');

        CarbonImmutable::setTestNow();
    }

    public function test_the_dashboard_message_widget_shows_and_links_tomorrows_message(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-30 08:00'));

        $congregation = Congregation::factory()->create(['name' => 'Congregatia Mesaj']);
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $main = Menu::factory()->create(['name' => 'Ciorba de mesaj', 'type' => 'main']);
        DailyMeal::factory()->for($week)->for($congregation)->for($main)->create([
            'meal_date' => '2026-12-01',
            'estimated_people' => 25,
            'status' => 'confirmed',
        ]);

        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(TomorrowMessageWidget::class)
            ->assertSee('Mesajul de maine')
            ->assertSee('Congregatia Mesaj')
            ->assertSee('Ciorba de mesaj')
            ->assertSee('wa.me/?text=', escape: false);

        CarbonImmutable::setTestNow();
    }

    public function test_the_dashboard_message_widget_is_hidden_when_there_is_no_meal_tomorrow(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-30 08:00'));

        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(TomorrowMessageWidget::class)
            ->assertDontSee('Mesajul de maine');

        CarbonImmutable::setTestNow();
    }
}

