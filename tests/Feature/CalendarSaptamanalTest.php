<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\FoodFundTransaction;
use App\Models\Menu;
use App\Models\SupplyContribution;
use App\Models\SupplyItem;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\Week;
use App\Models\WorkTeam;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
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

    public function test_an_administrator_can_add_a_working_day_from_the_checkbox(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('toggleWorkingDay', '2026-11-30')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('daily_meals', [
            'week_id' => $week->id,
            'congregation_id' => $congregation->id,
        ]);
        $this->assertSame(1, DailyMeal::query()->whereDate('meal_date', '2026-11-30')->count());
    }

    public function test_removing_a_working_day_with_data_requires_confirmation_then_deletes_it(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $menu = Menu::factory()->create();
        $congregation->menus()->attach($menu);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create([
            'meal_date' => '2026-11-30',
            'estimated_people' => 30,
        ]);
        $administrator = User::factory()->create(['role' => 'admin']);

        $component = Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('toggleWorkingDay', '2026-11-30');

        $this->assertSame($dailyMeal->id, $component->instance()->pendingWorkingDayMealId);
        $this->assertDatabaseHas('daily_meals', ['id' => $dailyMeal->id]);

        $component->call('confirmDeleteWorkingDay')->assertHasNoErrors();

        $this->assertDatabaseMissing('daily_meals', ['id' => $dailyMeal->id]);
    }

    public function test_creating_a_new_dish_from_the_card_adds_it_to_the_catalog_and_selects_it(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->create([
            'meal_date' => '2026-11-30',
            'menu_id' => null,
        ]);
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openNewDish', $dailyMeal->id, 'main')
            ->set('newDishName', 'Sarmale de casa')
            ->call('createDish')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('menus', ['name' => 'Sarmale de casa', 'type' => 'main', 'is_active' => true]);
        $this->assertSame(
            Menu::query()->where('name', 'Sarmale de casa')->value('id'),
            $dailyMeal->fresh()->menu_id,
        );
    }

    public function test_an_administrator_can_mark_a_day_as_cooked_with_a_different_actual_dish_and_real_cost(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $planned = Menu::factory()->create(['type' => 'main']);
        $actual = Menu::factory()->create(['type' => 'main', 'name' => 'Fel gatit de fapt']);
        $congregation->menus()->attach([$planned->id, $actual->id]);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->for($planned)->create([
            'meal_date' => '2026-11-30',
        ]);
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->set("cookedFlags.{$dailyMeal->id}", true)
            ->set("actualMenus.{$dailyMeal->id}", $actual->id)
            ->set("actualCosts.{$dailyMeal->id}", 128.5)
            ->call('markCooked', $dailyMeal->id)
            ->assertHasNoErrors();

        $dailyMeal->refresh();

        $this->assertTrue($dailyMeal->is_cooked);
        $this->assertSame($actual->id, $dailyMeal->actual_menu_id);
        $this->assertSame('128.50', $dailyMeal->actual_cost);
    }

    public function test_a_coordinator_cannot_add_a_working_day(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        try {
            Livewire::actingAs($coordinator)
                ->test(CalendarSaptamanal::class)
                ->set('weekId', $week->id)
                ->call('toggleWorkingDay', '2026-11-30');
        } catch (AuthorizationException) {
            // asteptat
        }

        $this->assertSame(0, DailyMeal::query()->count());
    }

    public function test_an_administrator_creates_a_new_week_from_the_calendar(): void
    {
        $congregation = Congregation::factory()->create();
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->call('openCreateWeek')
            ->set('newWeekNumber', 12)
            ->set('newWeekStartDate', '2027-01-04')
            ->set('newWeekCongregationId', $congregation->id)
            ->call('createWeek')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('weeks', [
            'week_number' => 12,
            'congregation_id' => $congregation->id,
        ]);
        $this->assertSame(
            '2027-01-04',
            Week::query()->where('week_number', 12)->first()->start_date->toDateString(),
        );
    }

    public function test_a_coordinator_cannot_create_a_week_from_the_calendar(): void
    {
        $congregation = Congregation::factory()->create();
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        try {
            Livewire::actingAs($coordinator)
                ->test(CalendarSaptamanal::class)
                ->call('openCreateWeek');
        } catch (AuthorizationException) {
            // asteptat
        }

        $this->assertSame(0, Week::query()->count());
    }

    public function test_an_administrator_deletes_a_week_with_its_meals_from_the_calendar(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $menu = Menu::factory()->create();
        $congregation->menus()->attach($menu);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create(['meal_date' => '2026-11-30']);
        $administrator = User::factory()->create(['role' => 'admin']);

        $component = Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openDeleteWeek', $week->id);

        $this->assertSame($week->id, $component->instance()->pendingDeleteWeekId);

        $component->call('confirmDeleteWeek')->assertHasNoErrors();

        $this->assertDatabaseMissing('weeks', ['id' => $week->id]);
        $this->assertDatabaseMissing('daily_meals', ['id' => $dailyMeal->id]);
    }

    public function test_an_administrator_adds_a_person_with_a_role_to_a_day(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->create(['meal_date' => '2026-11-30']);
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openPeople', $dailyMeal->id)
            ->set('volunteerName', 'Ion Popescu')
            ->set('volunteerRole', 'bucatar')
            ->set('volunteerPhone', '0722000111')
            ->call('saveVolunteer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('volunteers', [
            'daily_meal_id' => $dailyMeal->id,
            'name' => 'Ion Popescu',
            'role' => 'bucatar',
            'phone' => '0722000111',
        ]);
    }

    public function test_a_coordinator_can_add_a_person_only_to_their_own_day(): void
    {
        $own = Congregation::factory()->create();
        $other = Congregation::factory()->create();
        $week = Week::factory()->for($own)->create(['start_date' => '2026-11-30']);
        $ownMeal = DailyMeal::factory()->for($week)->for($own)->create(['meal_date' => '2026-11-30']);
        $otherMeal = DailyMeal::factory()->for($week)->for($other)->create(['meal_date' => '2026-12-01']);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $own->id]);

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openPeople', $ownMeal->id)
            ->set('volunteerName', 'Ana Ionescu')
            ->set('volunteerRole', 'ajutor')
            ->call('saveVolunteer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('volunteers', ['daily_meal_id' => $ownMeal->id, 'name' => 'Ana Ionescu']);

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openPeople', $otherMeal->id)
            ->assertForbidden();

        $this->assertDatabaseMissing('volunteers', ['daily_meal_id' => $otherMeal->id]);
    }

    public function test_an_administrator_adds_a_work_team_to_a_day(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->create(['meal_date' => '2026-11-30']);
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openTeams', $dailyMeal->id)
            ->set('teamName', 'Echipa bucatarie')
            ->set('teamMemberCount', 6)
            ->set('teamStartsAt', '08:00')
            ->set('teamEndsAt', '12:00')
            ->set('teamLocation', 'Cort')
            ->call('saveTeam')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('work_teams', [
            'daily_meal_id' => $dailyMeal->id,
            'name' => 'Echipa bucatarie',
            'member_count' => 6,
            'location' => 'Cort',
        ]);
    }

    public function test_the_day_card_summarizes_food_fund_transactions(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->create(['meal_date' => '2026-11-30']);
        $administrator = User::factory()->create(['role' => 'admin']);

        FoodFundTransaction::create([
            'type' => 'income',
            'transaction_date' => '2026-11-30',
            'amount' => 500,
            'category' => 'donation',
            'description' => 'Donatie',
        ]);
        FoodFundTransaction::create([
            'type' => 'expense',
            'transaction_date' => '2026-11-30',
            'amount' => 120,
            'category' => 'ingredients',
            'description' => 'Achizitie ingrediente',
        ]);

        $component = Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id);

        $fund = $component->instance()->foodFundByDate;

        $this->assertSame(500.0, $fund['2026-11-30']['income']);
        $this->assertSame(120.0, $fund['2026-11-30']['expense']);
        $this->assertSame(380.0, $fund['2026-11-30']['balance']);
    }

    public function test_the_calendar_flags_roles_without_an_assigned_person(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->create(['meal_date' => '2026-11-30']);
        WorkTeam::create([
            'daily_meal_id' => $dailyMeal->id,
            'name' => 'bucatar',
        ]);
        Volunteer::factory()->for($dailyMeal)->create(['role' => 'aprovizionare']);
        $administrator = User::factory()->create(['role' => 'admin']);

        $component = Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id);

        $uncovered = $component->instance()->uncoveredRoles;

        $this->assertArrayHasKey('2026-11-30', $uncovered);
        $this->assertStringContainsString('bucatar', $uncovered['2026-11-30']);
    }

    public function test_the_calendar_builds_whatsapp_messages_for_the_week_and_days(): void
    {
        $congregation = Congregation::factory()->create(['name' => 'Congregatia Sud']);
        $week = Week::factory()->for($congregation)->create(['week_number' => 7, 'start_date' => '2026-11-30']);
        $menu = Menu::factory()->create(['name' => 'Pilaf']);
        $congregation->menus()->attach($menu);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create([
            'meal_date' => '2026-11-30',
            'estimated_people' => 40,
        ]);
        $administrator = User::factory()->create(['role' => 'admin']);

        $component = Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id);

        $messages = $component->instance()->whatsappMessages;

        $this->assertStringContainsString('Congregatia Sud', $messages['days'][$dailyMeal->id]);
        $this->assertStringContainsString('Pilaf', $messages['days'][$dailyMeal->id]);
        $this->assertStringContainsString('40', $messages['days'][$dailyMeal->id]);
        $this->assertStringContainsString('Planul saptamanii 7', $messages['week']);
    }

    public function test_a_person_with_allergies_requires_details(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->create(['meal_date' => '2026-11-30']);
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openPeople', $dailyMeal->id)
            ->set('volunteerName', 'Maria')
            ->set('volunteerRole', 'bucatar')
            ->set('volunteerHasAllergies', true)
            ->set('volunteerAllergyDetails', '')
            ->call('saveVolunteer')
            ->assertHasErrors('volunteerAllergyDetails');
    }

    public function test_the_people_and_teams_modals_render_on_the_day_card(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['start_date' => '2026-11-30']);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->create(['meal_date' => '2026-11-30']);
        Volunteer::factory()->for($dailyMeal)->create(['name' => 'Petru', 'role' => 'bucatar']);
        WorkTeam::create(['daily_meal_id' => $dailyMeal->id, 'name' => 'Echipa 1']);
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openPeople', $dailyMeal->id)
            ->assertSee('Persoane si roluri')
            ->assertSee('Petru')
            ->call('closePeople')
            ->call('openTeams', $dailyMeal->id)
            ->assertSee('Echipe de lucru')
            ->assertSee('Echipa 1');
    }
}
