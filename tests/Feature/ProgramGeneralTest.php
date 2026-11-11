<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Filament\Pages\ProgramGeneral;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\FoodFundTransaction;
use App\Models\Menu;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgramGeneralTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_coordinator_sees_all_congregations_days_without_costs_portions_people_or_contributions(): void
    {
        $own = Congregation::factory()->create(['name' => 'Congregatia mea']);
        $other = Congregation::factory()->create(['name' => 'Balta Alba']);
        $week = Week::factory()->for($own)->create(['week_number' => 9, 'start_date' => '2026-12-07']);
        $main = Menu::factory()->create(['name' => 'Pilaf general']);
        $soup = Menu::factory()->create(['name' => 'Ciorba generala', 'type' => 'soup']);
        $dessert = Menu::factory()->create(['name' => 'Napolitana generala', 'type' => 'dessert']);
        $actual = Menu::factory()->create(['name' => 'Fel real general']);

        $ownDay = DailyMeal::factory()->for($week)->for($own)->for($main)->create([
            'meal_date' => '2026-12-07',
            'soup_menu_id' => $soup->id,
            'dessert_menu_id' => $dessert->id,
            'estimated_people' => 4567,
            'maximum_budget' => 777,
            'actual_cost' => 987.65,
            'is_cooked' => true,
            'actual_menu_id' => $actual->id,
            'delivery_time' => '18:30',
            'notes' => 'Nota secreta a zilei',
        ]);
        DailyMeal::factory()->for($week)->for($other)->for($main)->create(['meal_date' => '2026-12-08']);
        DailyMeal::factory()->for($week)->for($own)->create([
            'meal_date' => '2026-12-09',
            'is_free_day' => true,
            'menu_id' => null,
        ]);

        Volunteer::factory()->for($ownDay)->create(['name' => 'Voluntar Secret', 'phone' => '0700000000']);
        FoodFundTransaction::create([
            'type' => 'income',
            'transaction_date' => '2026-12-07',
            'amount' => 555,
            'category' => 'volunteer_contribution',
            'counterparty' => 'Contribuent Secret',
            'description' => 'Contributie secreta',
            'volunteer_contribution_status' => 'paid',
            'daily_meal_id' => $ownDay->id,
        ]);

        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $own->id]);

        $response = $this->actingAs($coordinator)
            ->get(ProgramGeneral::getUrl(panel: 'admin'));
        $response->assertOk()
            ->assertSee('Program general')
            ->assertSee('Congregatia mea')
            ->assertSee('Balta Alba')
            ->assertSee('Pilaf general')
            ->assertSee('Ciorba generala')
            ->assertSee('Napolitana generala')
            ->assertSee('Zi libera')
            ->assertSee('S-a gatit')
            ->assertSee('Fel real general');

        $content = $response->getContent();
        $position = strpos($content, 'program-page');
        $this->assertNotFalse($position);
        $section = substr($content, (int) $position);
        $this->assertStringNotContainsString('4567', $section);
        $this->assertStringNotContainsString('777', $section);
        $this->assertStringNotContainsString('987.65', $section);
        $this->assertStringNotContainsString('18:30', $section);
        $this->assertStringNotContainsString('Voluntar Secret', $section);
        $this->assertStringNotContainsString('Contribuent Secret', $section);
        $this->assertStringNotContainsString('Nota secreta a zilei', $section);
    }

    public function test_the_general_schedule_page_is_only_available_to_administrators_and_coordinators(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $coordinator = User::factory()->create(['role' => 'coordinator']);
        $construction = User::factory()->create(['role' => 'construction']);
        $kitchen = User::factory()->create(['role' => 'kitchen']);

        $this->actingAs($administrator)->get(ProgramGeneral::getUrl(panel: 'admin'))->assertOk();
        $this->actingAs($coordinator)->get(ProgramGeneral::getUrl(panel: 'admin'))->assertOk();
        $this->actingAs($construction)->get(ProgramGeneral::getUrl(panel: 'admin'))->assertForbidden();
        $this->actingAs($kitchen)->get(ProgramGeneral::getUrl(panel: 'admin'))->assertForbidden();
    }

    public function test_a_coordinator_cannot_edit_the_days_of_another_congregation_from_the_server(): void
    {
        $own = Congregation::factory()->create();
        $other = Congregation::factory()->create();
        $week = Week::factory()->for($other)->create(['start_date' => '2026-11-30']);
        $menu = Menu::factory()->create();
        $otherMeal = DailyMeal::factory()->for($week)->for($other)->for($menu)->create([
            'meal_date' => '2026-11-30',
            'estimated_people' => 10,
        ]);
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $own->id]);

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->set("plannedMenus.{$otherMeal->id}", $menu->id)
            ->call('savePlannedMenu', $otherMeal->id, 'main')
            ->assertForbidden();

        $this->assertSame($menu->id, $otherMeal->fresh()->menu_id);
        $this->assertSame(10, $otherMeal->fresh()->estimated_people);
    }
}
