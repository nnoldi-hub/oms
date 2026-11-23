<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Filament\Pages\PlanSprijin;
use App\Filament\Pages\ProgramGeneral;
use App\Filament\Resources\CongregationSupports\CongregationSupportResource;
use App\Filament\Resources\Donations\DonationResource;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Donation;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\User;
use App\Models\Week;
use App\Support\CongregationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PlanSprijinTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_links_to_support_plan_and_coordinators_are_denied(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cooks = Congregation::factory()->create([
            'name' => 'Bucuresti Est',
            'type' => CongregationRole::COOKS,
        ]);
        $week = Week::factory()->for($cooks)->create([
            'week_number' => 52,
            'start_date' => '2026-12-21',
        ]);

        $this->actingAs($admin);

        Livewire::test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->assertSee('Plan sprijin');

        $url = route('filament.admin.pages.plan-sprijin', ['week' => $week->id]);

        $this->get($url)->assertOk();

        $coordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $cooks->id,
        ]);

        $this->actingAs($coordinator);

        $this->assertFalse(PlanSprijin::canAccess());

        $this->get($url)->assertForbidden();
    }

    public function test_contact_status_notes_and_contributions_can_be_managed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $weekData = $this->weekWithContributors();
        ['week' => $week, 'meal' => $meal, 'contributors' => $contributors] = $weekData;

        $this->actingAs($admin);

        $noteKey = $meal->id.'_'.$contributors[0]->id;

        Livewire::test(PlanSprijin::class)
            ->set('weekFilter', (string) $week->id)
            ->assertSee('Congregatia Alpha')
            ->set('contactNotes.'.$noteKey, 'A confirmat gustările.')
            ->call('saveContactNotes', $meal->id, $contributors[0]->id)
            ->call('markContacted', $meal->id, $contributors[0]->id)
            ->call('openAddContribution', $meal->id, $contributors[0]->id)
            ->set('newKind', Donation::KIND_FRUIT)
            ->set('newDescription', 'banane')
            ->set('newQuantity', '3')
            ->set('newUnit', 'kg')
            ->call('saveContribution')
            ->assertHasNoErrors()
            ->assertSee('banane');

        $pivot = DB::table('congregation_daily_meal')
            ->where('daily_meal_id', $meal->id)
            ->where('congregation_id', $contributors[0]->id)
            ->firstOrFail();

        $this->assertSame('contactat', $pivot->contact_status);
        $this->assertNotNull($pivot->contacted_at);
        $this->assertSame('A confirmat gustările.', $pivot->contact_notes);

        $donation = Donation::query()->where('daily_meal_id', $meal->id)->firstOrFail();

        $this->assertSame(Donation::STATUS_PROMISED, $donation->status);
        $this->assertSame(Donation::KIND_FRUIT, $donation->kind);
        $this->assertSame('banane', $donation->description);
        $this->assertEquals(3, (float) $donation->quantity);
        $this->assertSame($contributors[0]->id, $donation->donor_congregation_id);

        Livewire::test(PlanSprijin::class)
            ->set('weekFilter', (string) $week->id)
            ->call('markReceived', $donation->id);

        $this->assertSame(Donation::STATUS_RECEIVED, $donation->fresh()->status);
    }

    public function test_warnings_flag_duplicate_products_missing_category_and_uncontacted_days(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ['week' => $week, 'meal' => $meal, 'contributors' => $contributors] = $this->weekWithContributors();

        foreach ($contributors as $congregation) {
            Donation::query()->create([
                'congregation_id' => $congregation->id,
                'donor_congregation_id' => $congregation->id,
                'daily_meal_id' => $meal->id,
                'date' => $meal->meal_date->toDateString(),
                'kind' => Donation::KIND_FRUIT,
                'status' => Donation::STATUS_PROMISED,
                'description' => 'banane',
                'quantity' => 1,
                'unit' => 'kg',
            ]);
        }

        $this->actingAs($admin);

        Livewire::test(PlanSprijin::class)
            ->set('weekFilter', (string) $week->id)
            ->assertSee('3 congregații aduc banane')
            ->assertSee('Nimeni nu aduce cafea/ceai')
            ->assertSee('necontactate');
    }

    public function test_food_contribution_shows_need_coverage_and_flags_an_over_offer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ['week' => $week, 'meal' => $meal, 'contributors' => $contributors] = $this->weekWithContributors();

        $ingredient = Ingredient::factory()->create([
            'name' => 'Orez',
            'unit' => 'kg',
            'unit_price' => 5,
        ]);
        $menu = Menu::factory()->create([
            'ingredients' => [[
                'ingredient_id' => $ingredient->id,
                'name' => 'Orez',
                'quantity_per_person' => 1,
                'unit' => 'kg',
                'estimated_unit_cost' => 5,
            ]],
            'packaging_cost' => 0,
        ]);
        $meal->update([
            'menu_id' => $menu->id,
            'estimated_people' => 10,
        ]);

        foreach ([
            [Donation::STATUS_RECEIVED, 6, $contributors[1]],
            [Donation::STATUS_PROMISED, 2, $contributors[2]],
        ] as [$status, $quantity, $donor]) {
            Donation::query()->create([
                'congregation_id' => $donor->id,
                'donor_congregation_id' => $donor->id,
                'daily_meal_id' => $meal->id,
                'date' => $meal->meal_date->toDateString(),
                'kind' => Donation::KIND_FOOD,
                'status' => $status,
                'description' => 'Orez',
                'quantity' => $quantity,
                'unit' => 'kg',
            ]);
        }

        $this->actingAs($admin);

        Livewire::test(PlanSprijin::class)
            ->set('weekFilter', (string) $week->id)
            ->call('openAddContribution', $meal->id, $contributors[0]->id)
            ->set('newKind', Donation::KIND_FOOD)
            ->set('newIngredientId', $ingredient->id)
            ->assertSee('Necesar 10 kg, acoperit 8 kg, mai trebuie 2 kg')
            ->set('newQuantity', '5')
            ->assertSee('Atenție: oferta depășește necesarul rămas cu 3 kg')
            ->set('newStorage', Donation::STORAGE_COLD)
            ->set('newDeliveryUntil', '2026-12-14T10:30')
            ->call('saveContribution')
            ->assertHasNoErrors();

        $donation = Donation::query()
            ->where('daily_meal_id', $meal->id)
            ->where('description', 'Orez')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($ingredient->id, $donation->ingredient_id);
        $this->assertSame(Donation::STORAGE_COLD, $donation->storage_method);
        $this->assertSame('2026-12-14 10:30:00', $donation->delivery_until->toDateTimeString());
        $this->assertSame('kg', $donation->unit);
    }

    public function test_unallocated_donations_can_be_allocated_to_a_day(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ['week' => $week, 'meal' => $meal, 'contributors' => $contributors] = $this->weekWithContributors();

        $donation = Donation::query()->create([
            'congregation_id' => $contributors[0]->id,
            'donor_congregation_id' => $contributors[0]->id,
            'date' => '2026-12-14',
            'kind' => Donation::KIND_FRUIT,
            'status' => Donation::STATUS_PROMISED,
            'description' => 'banane fără zi',
            'quantity' => 2,
            'unit' => 'kg',
        ]);

        $this->actingAs($admin);

        Livewire::test(PlanSprijin::class)
            ->set('weekFilter', (string) $week->id)
            ->assertSee('Donații nealocate (fără zi)')
            ->assertSee('Alocă unei zile')
            ->call('openAllocate', $donation->id)
            ->call('allocateDonation')
            ->assertHasNoErrors()
            ->assertSee('Nu există donații fără zi.');

        $this->assertSame($meal->id, $donation->fresh()->daily_meal_id);
        $this->assertSame('2026-12-14', $donation->fresh()->date->toDateString());
    }

    public function test_delivery_details_are_visible_only_to_admin_and_the_days_cooking_coordinator(): void
    {
        ['week' => $week, 'meal' => $meal, 'contributors' => $contributors] = $this->weekWithContributors();
        $cooks = $meal->cookingCongregation;

        Donation::query()->create([
            'congregation_id' => $contributors[0]->id,
            'donor_congregation_id' => $contributors[0]->id,
            'daily_meal_id' => $meal->id,
            'date' => $meal->meal_date->toDateString(),
            'kind' => Donation::KIND_FRUIT,
            'status' => Donation::STATUS_PROMISED,
            'description' => 'banane cu livrare',
            'quantity' => 2,
            'unit' => 'kg',
            'storage_method' => Donation::STORAGE_FROZEN,
            'delivery_until' => '2026-12-14 10:30:00',
        ]);

        $needle = 'Livrare până: 14.12 10:30';

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->assertSee('Păstrare și livrare')
            ->assertSee($needle);

        $this->actingAs(User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $cooks->id,
        ]));

        Livewire::test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->assertSee($needle);

        $otherCooks = Congregation::factory()->create([
            'name' => 'Bucuresti Titan',
            'type' => CongregationRole::COOKS,
        ]);

        $this->actingAs(User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $otherCooks->id,
        ]));

        Livewire::test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->assertDontSee($needle);

        $this->actingAs(User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $contributors[0]->id,
        ]));

        Livewire::test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->assertDontSee($needle);
    }

    public function test_support_assistant_manages_only_the_support_plan(): void
    {
        ['week' => $week, 'meal' => $meal, 'contributors' => $contributors] = $this->weekWithContributors();
        $assistant = User::factory()->create(['role' => 'support_assistant']);

        $this->actingAs($assistant);

        $this->get(PlanSprijin::getUrl(panel: 'admin'))->assertOk();

        Livewire::test(PlanSprijin::class)
            ->set('weekFilter', (string) $week->id)
            ->assertSee('Congregatia Alpha')
            ->call('openAddContribution', $meal->id, $contributors[0]->id)
            ->set('newDescription', 'Gustari pentru asistent')
            ->call('saveContribution')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('donations', [
            'description' => 'Gustari pentru asistent',
            'recorded_by' => $assistant->id,
        ]);

        $this->get(ProgramGeneral::getUrl(panel: 'admin'))->assertForbidden();
        $this->get(CongregationSupportResource::getUrl('index'))->assertForbidden();
        $this->get(DonationResource::getUrl('index'))->assertForbidden();
    }

    /** @return array{week: Week, meal: DailyMeal, contributors: list<Congregation>} */
    private function weekWithContributors(): array
    {
        $cooks = Congregation::factory()->create([
            'name' => 'Bucuresti Est',
            'type' => CongregationRole::COOKS,
        ]);
        $week = Week::factory()->for($cooks)->create([
            'week_number' => 51,
            'start_date' => '2026-12-14',
        ]);
        $meal = DailyMeal::factory()->for($week)->for($cooks)->create([
            'meal_date' => '2026-12-14',
        ]);

        $contributors = collect([
            'Congregatia Alpha',
            'Congregatia Beta',
            'Congregatia Gamma',
        ])->map(fn (string $name): Congregation => Congregation::factory()->create([
            'name' => $name,
            'type' => CongregationRole::CONTRIBUTES,
            'assistant_name' => 'Persoana de contact',
            'assistant_phone' => '0712000000',
            'assistant_email' => 'contact@example.test',
        ]))->values()->all();

        $meal->syncContributingCongregations(
            collect($contributors)->pluck('id')->map(fn ($id): int => (int) $id)->all(),
        );

        return ['week' => $week, 'meal' => $meal, 'contributors' => $contributors];
    }
}
