<?php

namespace Tests\Feature;

use App\Filament\Resources\CongregationSupports\CongregationSupportResource;
use App\Filament\Resources\CongregationSupports\Pages\ListCongregationSupports;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Donation;
use App\Models\SupplyContribution;
use App\Models\SupplyItem;
use App\Models\User;
use App\Models\Week;
use App\Services\CongregationSupportReportService;
use App\Support\CongregationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CongregationSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_admin_can_open_the_support_resource_and_reports(): void
    {
        $cooks = Congregation::factory()->create(['name' => 'Bucuresti Est', 'type' => CongregationRole::COOKS]);
        $contributor = Congregation::factory()->create(['name' => 'Congregatia Contribuitoare', 'type' => CongregationRole::CONTRIBUTES]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $this->assertTrue(CongregationSupportResource::canAccess());
        Livewire::test(ListCongregationSupports::class)
            ->assertCanSeeTableRecords([$cooks, $contributor]);

        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $cooks->id]);
        $this->actingAs($coordinator);
        $this->assertFalse(CongregationSupportResource::canAccess());
        $this->get(route('congregation-support-reports.show'))->assertForbidden();
        $this->get(route('congregation-support-reports.pdf'))->assertForbidden();
    }

    public function test_the_report_aggregates_contributions_donations_and_supplies_for_each_congregation(): void
    {
        $cooks = Congregation::factory()->create([
            'name' => 'Bucuresti Titan',
            'type' => CongregationRole::COOKS,
            'support_notes' => 'Sprijin constant cu transport.',
        ]);
        $contributor = Congregation::factory()->create([
            'name' => 'Congregatia Gustari',
            'type' => CongregationRole::CONTRIBUTES,
            'support_notes' => 'Aduce fructe sambata.',
        ]);
        $week = Week::factory()->for($cooks)->create([
            'week_number' => 50,
            'start_date' => '2026-12-07',
        ]);
        $meal = DailyMeal::factory()->for($week)->for($cooks)->create(['meal_date' => '2026-12-12']);
        $meal->contributingCongregations()->attach($contributor->id);

        Donation::factory()->for($contributor)->create([
            'kind' => Donation::KIND_FOOD,
            'status' => Donation::STATUS_RECEIVED,
            'date' => '2026-12-12',
            'amount_ron' => 75,
        ]);
        Donation::factory()->for($contributor)->create([
            'kind' => Donation::KIND_MONEY,
            'status' => Donation::STATUS_PROMISED,
            'date' => '2026-12-12',
            'amount_ron' => 25,
        ]);

        $item = SupplyItem::create([
            'name' => 'Cafea',
            'category' => 'snack',
            'unit' => 'pachet',
        ]);
        SupplyContribution::create([
            'congregation_id' => $contributor->id,
            'supply_item_id' => $item->id,
            'delivery_date' => '2026-12-12',
            'quantity' => 4,
            'delivery_status' => 'delivered',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $service = app(CongregationSupportReportService::class);
        $summary = $service->summary($contributor, '2026-12-01', '2026-12-31');

        $this->assertSame(1, $summary['contribution_days']);
        $this->assertSame(0, $summary['cooking_days']);
        $this->assertSame(2, $summary['donations_count']);
        $this->assertSame(100.0, $summary['donated_amount']);
        $this->assertSame(1, $summary['food_donations_count']);
        $this->assertSame(1, $summary['supply_count']);

        $rows = $service->rows('2026-12-01', '2026-12-31');
        $this->assertSame(1, $service->totals($rows)['contribution_days']);
        $this->assertSame(1, $service->totals($rows)['cooking_days']);
        $this->assertSame(100.0, $service->totals($rows)['donated_amount']);

        $this->get(route('congregation-support-reports.show', ['from' => '2026-12-01', 'to' => '2026-12-31']))
            ->assertOk()
            ->assertSee('Congregatia Gustari')
            ->assertSee('Sprijin constant cu transport.')
            ->assertSee('100.00 RON');

        $this->get(route('congregation-support-reports.pdf', ['from' => '2026-12-01']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_an_admin_can_add_support_from_the_resource(): void
    {
        $contributor = Congregation::factory()->create([
            'name' => 'Congregatia Cafea',
            'type' => CongregationRole::CONTRIBUTES,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(ListCongregationSupports::class)
            ->callTableAction('quickAdd', $contributor, [
                'kind' => Donation::KIND_FOOD,
                'status' => Donation::STATUS_RECEIVED,
                'date' => '2026-12-14',
                'description' => 'Cafea',
                'quantity' => 3,
                'unit' => 'pachet',
                'amount_ron' => 45,
            ]);

        $this->assertDatabaseHas('donations', [
            'congregation_id' => $contributor->id,
            'donor_congregation_id' => $contributor->id,
            'kind' => Donation::KIND_FOOD,
            'status' => Donation::STATUS_RECEIVED,
            'description' => 'Cafea',
            'amount_ron' => 45,
        ]);
    }
}
