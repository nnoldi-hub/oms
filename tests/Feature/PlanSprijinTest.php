<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Filament\Pages\PlanSprijin;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Donation;
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
