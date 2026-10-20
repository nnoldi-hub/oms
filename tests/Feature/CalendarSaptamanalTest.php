<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\SupplyContribution;
use App\Models\SupplyItem;
use App\Models\User;
use App\Models\Week;
use Carbon\CarbonImmutable;
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
        $dessert = Menu::factory()->create(['name' => 'Napolitana calendar', 'type' => 'dessert']);
        $congregation->menus()->attach([$menu->id, $dessert->id]);
        DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create([
            'meal_date' => '2026-12-07',
            'estimated_people' => 45,
            'dessert_menu_id' => $dessert->id,
        ]);
        $user = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $this->actingAs($user)
            ->get(CalendarSaptamanal::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSee('Calendar saptamanal')
            ->assertSee('Congregatia Nord')
            ->assertSee('Meniu calendar')
            ->assertSee('Napolitana calendar')
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

    public function test_a_coordinator_can_save_a_budget_and_contribution_for_their_day(): void
    {
        $congregation = Congregation::factory()->create();
        $menu = Menu::factory()->create([
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 10]],
            'packaging_cost' => 2,
        ]);
        $dailyMeal = DailyMeal::factory()->for(Week::factory()->for($congregation))->for($congregation)->for($menu)->create(['estimated_people' => 20]);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $dailyMeal->week_id)
            ->set("maximumBudget.{$dailyMeal->id}", 300)
            ->set("contributorCount.{$dailyMeal->id}", 4)
            ->call('saveBudget', $dailyMeal->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('daily_meals', ['id' => $dailyMeal->id, 'maximum_budget' => 300, 'contributor_count' => 4]);
    }

    public function test_a_coordinator_only_sees_their_own_congregations_days_and_contributions(): void
    {
        $own = Congregation::factory()->create(['name' => 'Congregatia mea']);
        $other = Congregation::factory()->create(['name' => 'Congregatia vecina']);
        $week = Week::factory()->for($own)->create(['start_date' => '2026-12-07']);
        $menu = Menu::factory()->create(['name' => 'Meniu comun']);
        $own->menus()->attach($menu);
        $other->menus()->attach($menu);

        DailyMeal::factory()->for($week)->for($own)->for($menu)->create([
            'meal_date' => '2026-12-07',
        ]);
        DailyMeal::factory()->for($week)->for($other)->for($menu)->create([
            'meal_date' => '2026-12-08',
        ]);

        $item = SupplyItem::create(['name' => 'Apa', 'category' => 'water', 'unit' => 'buc']);
        SupplyContribution::create([
            'congregation_id' => $own->id,
            'supply_item_id' => $item->id,
            'delivery_date' => '2026-12-08',
            'quantity' => 30,
            'responsible_name' => 'Responsabil propriu',
        ]);
        SupplyContribution::create([
            'congregation_id' => $other->id,
            'supply_item_id' => $item->id,
            'delivery_date' => '2026-12-08',
            'quantity' => 99,
            'responsible_name' => 'Responsabil vecin',
        ]);

        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $own->id]);

        $component = Livewire::actingAs($coordinator)->test(CalendarSaptamanal::class);

        $this->assertSame(1, $component->instance()->selectedWeek->dailyMeals->count());
        $this->assertSame($own->id, $component->instance()->selectedWeek->dailyMeals->first()->congregation_id);

        $indicators = $component->instance()->supplyIndicators;
        $rendered = json_encode($indicators);

        $this->assertStringNotContainsString('Responsabil vecin', $rendered);
        $this->assertStringNotContainsString('99', $rendered);
    }

    public function test_moving_a_day_keeps_the_menu_and_only_changes_the_date(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $menu = Menu::factory()->create();
        $congregation->menus()->attach($menu);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create([
            'meal_date' => '2026-11-30',
            'estimated_people' => 33,
        ]);
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openMoveDay', $dailyMeal->id)
            ->set('moveDayDate', '2026-12-05')
            ->call('moveDay')
            ->assertHasNoErrors();

        $dailyMeal->refresh();

        $this->assertSame('2026-12-05', $dailyMeal->meal_date->toDateString());
        $this->assertSame($menu->id, $dailyMeal->menu_id);
        $this->assertSame($congregation->id, $dailyMeal->congregation_id);
        $this->assertSame(33, $dailyMeal->estimated_people);
    }

    public function test_marking_a_free_day_without_shifting_keeps_the_rest_unchanged(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $menu = Menu::factory()->create();
        $congregation->menus()->attach($menu);
        $first = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create(['meal_date' => '2026-11-30']);
        $second = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create(['meal_date' => '2026-12-01']);
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openFreeDay', $first->id)
            ->set('freeDayShiftRest', false)
            ->call('markFreeDay')
            ->assertHasNoErrors();

        $first->refresh();
        $second->refresh();

        $this->assertTrue($first->is_free_day);
        $this->assertNull($first->menu_id);
        $this->assertSame('2026-12-01', $second->meal_date->toDateString());
    }

    public function test_marking_a_free_day_and_shifting_moves_the_following_days_by_one(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $menu = Menu::factory()->create();
        $congregation->menus()->attach($menu);
        $first = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create(['meal_date' => '2026-11-30']);
        $second = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create(['meal_date' => '2026-12-01']);
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openFreeDay', $first->id)
            ->set('freeDayShiftRest', true)
            ->call('markFreeDay')
            ->assertHasNoErrors();

        $this->assertSame('2026-12-02', $second->fresh()->meal_date->toDateString());
    }

    public function test_moving_a_week_shifts_all_of_its_days(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $menu = Menu::factory()->create();
        $congregation->menus()->attach($menu);

        foreach (range(0, 4) as $dayIndex) {
            DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create([
                'meal_date' => CarbonImmutable::parse('2026-11-30')->addDays($dayIndex),
            ]);
        }

        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openMoveWeek', $week->id)
            ->set('moveWeekDays', 2)
            ->call('moveWeek')
            ->assertHasNoErrors();

        $dates = DailyMeal::query()->orderBy('meal_date')->pluck('meal_date')->map(fn ($date) => $date->toDateString())->all();

        $this->assertSame(['2026-12-02', '2026-12-03', '2026-12-04', '2026-12-05', '2026-12-06'], $dates);
        $this->assertSame('2026-12-02', $week->fresh()->start_date->toDateString());
    }

    public function test_a_coordinator_cannot_move_a_day(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $menu = Menu::factory()->create();
        $congregation->menus()->attach($menu);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create(['meal_date' => '2026-11-30']);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openMoveDay', $dailyMeal->id);

        $this->assertSame('2026-11-30', $dailyMeal->fresh()->meal_date->toDateString());
        $this->assertFalse($dailyMeal->fresh()->is_free_day);
    }

    public function test_the_move_controls_are_only_rendered_for_administrators(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $menu = Menu::factory()->create();
        $congregation->menus()->attach($menu);
        DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create(['meal_date' => '2026-11-30']);

        $administrator = User::factory()->create(['role' => 'admin']);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $this->actingAs($administrator)
            ->get(CalendarSaptamanal::getUrl(panel: 'admin'))
            ->assertSee('Mută ziua')
            ->assertSee('Mută săptămâna');

        $this->actingAs($coordinator)
            ->get(CalendarSaptamanal::getUrl(panel: 'admin'))
            ->assertDontSee('Mută săptămâna');
    }
}
