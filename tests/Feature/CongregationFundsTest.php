<?php

namespace Tests\Feature;

use App\Filament\Pages\CosturiBuget;
use App\Filament\Resources\FoodFundTransactions\FoodFundTransactionResource;
use App\Filament\Resources\FoodFundTransactions\Pages\CreateFoodFundTransaction;
use App\Filament\Resources\FoodFundTransactions\Pages\ListFoodFundTransactions;
use App\Filament\Resources\SupplyContributions\Pages\ListSupplyContributions;
use App\Filament\Resources\SupplyContributions\SupplyContributionResource;
use App\Filament\Resources\SupplyItems\SupplyItemResource;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\FoodFundTransaction;
use App\Models\Menu;
use App\Models\SupplyContribution;
use App\Models\SupplyItem;
use App\Models\User;
use App\Models\Week;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CongregationFundsTest extends TestCase
{
    use RefreshDatabase;

    private Congregation $own;

    private Congregation $other;

    private User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->own = Congregation::factory()->create(['name' => 'Balta Alba']);
        $this->other = Congregation::factory()->create(['name' => 'Bucuresti Est']);
        $this->coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $this->own->id]);
    }

    private function transaction(Congregation $congregation, float $amount, string $date = '2026-12-01'): FoodFundTransaction
    {
        return FoodFundTransaction::create([
            'type' => 'expense',
            'transaction_date' => $date,
            'amount' => $amount,
            'description' => 'Cheltuiala '.$congregation->name,
            'congregation_id' => $congregation->id,
        ]);
    }

    public function test_a_coordinator_sees_only_the_funds_of_their_congregation(): void
    {
        $ownTransaction = $this->transaction($this->own, 10);
        $otherTransaction = $this->transaction($this->other, 20);

        $this->actingAs($this->coordinator);

        $this->assertTrue(FoodFundTransactionResource::canAccess());
        $this->assertTrue(FoodFundTransactionResource::canEdit($ownTransaction));
        $this->assertFalse(FoodFundTransactionResource::canEdit($otherTransaction));

        Livewire::test(ListFoodFundTransactions::class)
            ->assertCanSeeTableRecords([$ownTransaction])
            ->assertCanNotSeeTableRecords([$otherTransaction]);
    }

    public function test_a_coordinator_records_funds_only_for_their_congregation(): void
    {
        $this->actingAs($this->coordinator);

        Livewire::test(CreateFoodFundTransaction::class)
            ->fillForm([
                'type' => 'expense',
                'transaction_date' => '2026-12-01',
                'amount' => 75,
                'description' => 'Legume piata',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('food_fund_transactions', ['amount' => 75, 'congregation_id' => $this->own->id]);

        // Chiar daca incearca alta congregatie, operatiunea ramane pe congregatia sa.
        $forced = FoodFundTransaction::create([
            'type' => 'income',
            'transaction_date' => '2026-12-01',
            'amount' => 5,
            'description' => 'Incercare',
            'congregation_id' => $this->other->id,
        ]);
        $this->assertSame($this->own->id, (int) $forced->congregation_id);
    }

    public function test_a_coordinator_cannot_change_the_funds_of_another_congregation(): void
    {
        $otherTransaction = $this->transaction($this->other, 20);

        $this->actingAs($this->coordinator);

        $this->expectException(AuthorizationException::class);

        $otherTransaction->update(['amount' => 1]);
    }

    public function test_a_transaction_linked_to_a_day_takes_the_congregation_of_the_day(): void
    {
        $week = Week::factory()->for($this->other)->create(['start_date' => '2026-11-30']);
        $meal = DailyMeal::factory()->for($week)->for($this->other)->create(['meal_date' => '2026-11-30']);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $transaction = FoodFundTransaction::create([
            'type' => 'income',
            'transaction_date' => '2026-11-30',
            'amount' => 50,
            'description' => 'Contributie voluntar',
            'daily_meal_id' => $meal->id,
        ]);

        $this->assertSame($this->other->id, (int) $transaction->congregation_id);
    }

    public function test_a_coordinator_sees_only_the_contributions_of_their_congregation(): void
    {
        $item = SupplyItem::create(['name' => 'Apa', 'category' => 'water', 'unit' => 'buc']);
        $ownContribution = SupplyContribution::create([
            'congregation_id' => $this->own->id, 'supply_item_id' => $item->id,
            'delivery_date' => '2026-12-01', 'quantity' => 10, 'delivery_status' => 'confirmed',
        ]);
        $otherContribution = SupplyContribution::create([
            'congregation_id' => $this->other->id, 'supply_item_id' => $item->id,
            'delivery_date' => '2026-12-01', 'quantity' => 5, 'delivery_status' => 'confirmed',
        ]);

        $this->actingAs($this->coordinator);

        $this->assertTrue(SupplyContributionResource::canCreate());

        Livewire::test(ListSupplyContributions::class)
            ->assertCanSeeTableRecords([$ownContribution])
            ->assertCanNotSeeTableRecords([$otherContribution]);

        $forced = SupplyContribution::create([
            'congregation_id' => $this->other->id, 'supply_item_id' => $item->id,
            'delivery_date' => '2026-12-02', 'quantity' => 3, 'delivery_status' => 'confirmed',
        ]);
        $this->assertSame($this->own->id, (int) $forced->congregation_id);
    }

    public function test_the_supply_pages_load_for_a_coordinator(): void
    {
        $this->transaction($this->own, 10);

        $this->actingAs($this->coordinator);

        foreach ([
            FoodFundTransactionResource::getUrl('index'),
            FoodFundTransactionResource::getUrl('create'),
            SupplyContributionResource::getUrl('index'),
            SupplyContributionResource::getUrl('create'),
            CosturiBuget::getUrl(),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_the_shared_stock_stays_with_the_administrator(): void
    {
        $this->actingAs($this->coordinator);

        $this->assertFalse(SupplyItemResource::canAccess());
    }

    public function test_the_budget_page_shows_a_coordinator_only_their_weeks_and_expenses(): void
    {
        $menu = Menu::factory()->create([
            'type' => 'main',
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 10]],
            'packaging_cost' => 0,
        ]);

        $ownWeek = Week::factory()->for($this->own)->create(['week_number' => 1, 'start_date' => '2026-11-30']);
        $otherWeek = Week::factory()->for($this->other)->create(['week_number' => 2, 'start_date' => '2026-12-07']);
        DailyMeal::factory()->for($ownWeek)->for($this->own)->for($menu)->create(['meal_date' => '2026-11-30', 'estimated_people' => 10]);
        DailyMeal::factory()->for($otherWeek)->for($this->other)->for($menu)->create(['meal_date' => '2026-12-07', 'estimated_people' => 20]);

        $this->transaction($this->own, 8, '2026-12-01');
        $this->transaction($this->other, 25, '2026-12-08');

        $component = Livewire::actingAs($this->coordinator)->test(CosturiBuget::class);
        $page = $component->instance();

        $this->assertSame([$ownWeek->id], $page->weeks->pluck('id')->all());
        $this->assertSame($ownWeek->id, $page->weekId);
        $this->assertSame(1, $page->budget['total_weeks']);
        $this->assertSame(10.0, $page->budget['projected_total']);
        $this->assertSame(8.0, $page->budget['recorded_expenses']);
        $this->assertSame(8.0, $page->spentThisWeek);
        $this->assertFalse($page->canSeeStock);
    }
}
