<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Filament\Resources\DailyMeals\DailyMealResource;
use App\Filament\Resources\SupplyContributions\SupplyContributionResource;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\SupplyContribution;
use App\Models\SupplyItem;
use App\Models\User;
use App\Models\Week;
use App\Models\WeekChangeRequest;
use App\Support\CongregationAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CoordinatorRoleModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_cooking_congregation_is_not_a_contributor_and_cooks_coordinator_can_manage(): void
    {
        [$cooks, $first, $second, $third] = Congregation::factory()->count(4)->create();
        $week = Week::factory()->for($cooks)->create();
        $meal = DailyMeal::factory()->for($week)->for($cooks)->create(['meal_date' => '2026-12-07']);
        $meal->syncContributingCongregations([$cooks->id, $first->id, $second->id, $third->id]);

        $this->assertSame([$first->id, $second->id, $third->id], $meal->fresh()->contributingCongregationIds());
        $this->assertSame($cooks->id, $meal->fresh()->congregation_id);

        $cookCoordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $cooks->id,
        ]);

        $this->assertTrue(CongregationAccess::canManageMeal($cookCoordinator, $meal));
        $this->assertTrue(Gate::forUser($cookCoordinator)->allows('update', $meal));

        $this->actingAs($cookCoordinator);
        $this->assertTrue(DailyMealResource::canEdit($meal));
        $this->assertTrue(Gate::allows('viewFull', $meal));
    }

    public function test_contributor_coordinator_cannot_edit_menu_or_see_costs(): void
    {
        [$cooks, $contributor, $other] = Congregation::factory()->count(3)->create();
        $week = Week::factory()->for($cooks)->create();
        $menu = Menu::factory()->create(['name' => 'Secret Menu']);
        $meal = DailyMeal::factory()->for($week)->for($cooks)->for($menu)->create([
            'meal_date' => '2026-12-07',
            'maximum_budget' => 999,
            'notes' => 'Cost secret',
        ]);
        $meal->syncContributingCongregations([$contributor->id, $other->id]);

        $contributorCoordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $contributor->id,
        ]);

        $this->assertTrue(CongregationAccess::canSeeMeal($contributorCoordinator, $meal));
        $this->assertFalse(CongregationAccess::canManageMeal($contributorCoordinator, $meal));
        $this->assertTrue(CongregationAccess::isContributorOnly($contributorCoordinator, $meal));
        $this->assertFalse(Gate::forUser($contributorCoordinator)->allows('update', $meal));

        $this->actingAs($contributorCoordinator);
        $this->assertFalse(DailyMealResource::canEdit($meal));
        $this->assertFalse(Gate::allows('viewFull', $meal));
        $this->assertFalse(DailyMealResource::canViewSensitiveFields($meal));
    }

    public function test_contributor_coordinator_sees_only_their_contribution_days(): void
    {
        [$cooks, $mine, $other] = Congregation::factory()->count(3)->create();
        $week = Week::factory()->for($cooks)->create();

        $mineDay = DailyMeal::factory()->for($week)->for($cooks)->create(['meal_date' => '2026-12-07']);
        $mineDay->syncContributingCongregations([$mine->id, $other->id]);

        $otherDay = DailyMeal::factory()->for($week)->for($cooks)->create(['meal_date' => '2026-12-08']);
        $otherDay->syncContributingCongregations([$other->id]);

        $contributorCoordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $mine->id,
        ]);

        $this->assertTrue(CongregationAccess::canSeeMeal($contributorCoordinator, $mineDay));
        $this->assertFalse(CongregationAccess::canSeeMeal($contributorCoordinator, $otherDay));
    }

    public function test_contributor_cannot_open_full_reports_or_the_preparation_sheet(): void
    {
        [$cooks, $contributor] = Congregation::factory()->count(2)->create();
        $week = Week::factory()->for($cooks)->create();
        $meal = DailyMeal::factory()->for($week)->for($cooks)->create([
            'meal_date' => '2026-12-07',
            'maximum_budget' => 500,
        ]);
        $meal->syncContributingCongregations([$contributor->id]);

        $contributorCoordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $contributor->id,
        ]);
        $cookCoordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $cooks->id,
        ]);

        $this->actingAs($contributorCoordinator)
            ->get(route('weekly-reports.show', $week))
            ->assertForbidden();
        $this->get(route('weekly-plan-pdfs.show', $week))
            ->assertForbidden();
        $this->get(route('daily-meal-preparation-sheets.show', $meal))
            ->assertForbidden();
        $this->get(route('congregation-weekly-reports.show', [$week, $contributor]))
            ->assertForbidden();

        $this->actingAs($cookCoordinator)
            ->get(route('weekly-reports.show', $week))
            ->assertOk();
        $this->get(route('weekly-plan-pdfs.show', $week))
            ->assertOk();
        $this->get(route('daily-meal-preparation-sheets.show', $meal))
            ->assertOk();
    }

    public function test_calendar_hides_financial_data_and_other_contacts_from_a_contributor(): void
    {
        [$cooks, $contributor] = Congregation::factory()->count(2)->create();
        $other = Congregation::factory()->create(['assistant_name' => 'Contact care nu trebuie vazut']);
        $replacement = Congregation::factory()->create();
        $week = Week::factory()->for($cooks)->create();
        $meal = DailyMeal::factory()->for($week)->for($cooks)->create([
            'meal_date' => '2026-12-07',
            'maximum_budget' => 750,
        ]);
        $meal->syncContributingCongregations([$contributor->id, $other->id, $replacement->id]);

        $contributorCoordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $contributor->id,
        ]);

        Livewire::actingAs($contributorCoordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->assertSee($cooks->name)
            ->assertSee($contributor->name)
            ->assertDontSee('Buget estimat, cost si contributii')
            ->assertDontSee('Cost estimat')
            ->assertDontSee('Cost real')
            ->assertDontSee('Sumar financiar saptamanal')
            ->assertDontSee('Printeaza ziua')
            ->assertDontSee('Raport saptamanal')
            ->assertDontSee('Persoane (')
            ->assertDontSee('Copiaza mesajul')
            ->assertDontSee('Contact care nu trebuie vazut');
    }

    public function test_a_contributor_can_request_only_the_replacement_of_their_own_contribution(): void
    {
        [$cooks, $mine, $other, $replacement] = Congregation::factory()->count(4)->create();
        $week = Week::factory()->for($cooks)->create();
        $meal = DailyMeal::factory()->for($week)->for($cooks)->create(['meal_date' => '2026-12-07']);
        $meal->syncContributingCongregations([$mine->id, $other->id, $replacement->id]);

        $contributorCoordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $mine->id,
        ]);

        Livewire::actingAs($contributorCoordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openDayChange', $meal->id, 'replace')
            ->assertSet('dayChangeMealId', $meal->id)
            ->set('dayChangeReason', 'Nu pot participa in aceasta zi.')
            ->set('dayChangeFromCongregationId', $other->id)
            ->set('dayChangeToCongregationId', $replacement->id)
            ->call('submitDayChange')
            ->assertHasErrors('dayChangeFromCongregationId');

        $this->assertSame(0, WeekChangeRequest::count());

        Livewire::actingAs($contributorCoordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openDayChange', $meal->id, 'replace')
            ->set('dayChangeReason', 'Nu pot participa in aceasta zi.')
            ->set('dayChangeFromCongregationId', $mine->id)
            ->set('dayChangeToCongregationId', $cooks->id)
            ->call('submitDayChange')
            ->assertHasErrors('dayChangeToCongregationId');

        $this->assertSame(0, WeekChangeRequest::count());

        Livewire::actingAs($contributorCoordinator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->call('openDayChange', $meal->id, 'replace')
            ->set('dayChangeReason', 'Nu pot participa in aceasta zi.')
            ->set('dayChangeFromCongregationId', $mine->id)
            ->set('dayChangeToCongregationId', Congregation::factory()->create()->id)
            ->call('submitDayChange')
            ->assertHasNoErrors();

        $request = WeekChangeRequest::query()->firstOrFail();

        $this->assertSame('replace', $request->change_type);
        $this->assertSame($mine->id, $request->from_congregation_id);
        $this->assertSame($meal->id, $request->daily_meal_id);
        $this->assertSame($cooks->id, $meal->fresh()->congregation_id);
    }

    public function test_a_contributor_can_manage_only_their_own_supply_contributions(): void
    {
        [$cooks, $mine, $other] = Congregation::factory()->count(3)->create();
        $item = SupplyItem::create([
            'name' => 'Gustare test',
            'category' => 'snack',
            'unit' => 'buc',
            'unit_cost' => 1,
            'current_stock' => 0,
            'minimum_stock' => 0,
            'is_active' => true,
        ]);

        $mineContribution = SupplyContribution::create([
            'congregation_id' => $mine->id,
            'supply_item_id' => $item->id,
            'delivery_date' => '2026-12-07',
            'quantity' => 10,
            'delivery_status' => 'confirmed',
        ]);
        $otherContribution = SupplyContribution::create([
            'congregation_id' => $other->id,
            'supply_item_id' => $item->id,
            'delivery_date' => '2026-12-07',
            'quantity' => 20,
            'delivery_status' => 'confirmed',
        ]);

        $contributorCoordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $mine->id,
        ]);

        $this->actingAs($contributorCoordinator);

        $this->assertTrue(SupplyContributionResource::canEdit($mineContribution));
        $this->assertFalse(SupplyContributionResource::canEdit($otherContribution));
        $this->assertFalse(SupplyContributionResource::canDelete($otherContribution));

        $this->assertSame(
            [$mineContribution->id],
            SupplyContributionResource::getEloquentQuery()->pluck('id')->all(),
        );

        $this->expectException(AuthorizationException::class);

        $otherContribution->quantity = 30;
        $otherContribution->save();
    }
}
