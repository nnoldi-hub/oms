<?php

namespace Tests\Feature;

use App\Filament\Pages\CosturiBuget;
use App\Filament\Resources\FoodFundTransactions\FoodFundTransactionResource;
use App\Filament\Resources\FoodFundTransactions\Pages\CreateFoodFundTransaction;
use App\Models\Congregation;
use App\Models\Donation;
use App\Models\FoodFundTransaction;
use App\Models\User;
use App\Services\DonationFundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DonationFundFlowTest extends TestCase
{
    use RefreshDatabase;

    private function moneyDonation(Congregation $congregation, float $amount = 200): Donation
    {
        return Donation::factory()->for($congregation)->create([
            'kind' => Donation::KIND_MONEY,
            'status' => Donation::STATUS_RECEIVED,
            'date' => '2026-12-01',
            'amount_ron' => $amount,
            'donor_name' => 'Ana Maria',
            'description' => 'Donație pentru alimente',
            'notes' => 'Transfer bancar',
        ]);
    }

    public function test_a_received_money_donation_creates_one_explained_income_transaction(): void
    {
        $congregation = Congregation::factory()->create();
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $this->actingAs($coordinator);

        $donation = $this->moneyDonation($congregation, 300);
        $income = $donation->incomeTransaction()->firstOrFail();

        $this->assertSame(1, FoodFundTransaction::query()->where('donation_id', $donation->id)->count());
        $this->assertSame('2026-12-01', $income->transaction_date->toDateString());
        $this->assertSame(300.0, (float) $income->amount);
        $this->assertSame('donation', $income->category);
        $this->assertStringContainsString('Ana Maria', $income->description);
        $this->assertStringContainsString('Transfer bancar', $income->description);
        $this->assertSame($congregation->id, (int) $income->congregation_id);
        $this->assertFalse(FoodFundTransactionResource::canEdit($income));

        $donation->update(['amount_ron' => 250, 'date' => '2026-12-03']);

        $this->assertSame(1, FoodFundTransaction::query()->where('donation_id', $donation->id)->count());
        $this->assertSame(250.0, (float) $income->fresh()->amount);
        $this->assertSame('2026-12-03', $income->fresh()->transaction_date->toDateString());

        $donation->update(['status' => Donation::STATUS_PROMISED]);

        $this->assertSame(0, FoodFundTransaction::query()->where('donation_id', $donation->id)->count());
    }

    public function test_expenses_are_linked_to_the_donation_and_the_budget_shows_what_remains(): void
    {
        $congregation = Congregation::factory()->create();
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $this->actingAs($coordinator);

        $donation = $this->moneyDonation($congregation);

        FoodFundTransaction::query()->create([
            'type' => 'expense',
            'transaction_date' => '2026-12-05',
            'amount' => 75,
            'category' => 'food',
            'counterparty' => 'Piața Obor',
            'description' => 'Legume și orez pentru masa de sâmbătă',
            'congregation_id' => $congregation->id,
            'donation_id' => $donation->id,
            'recorded_by' => $coordinator->id,
        ]);

        $service = app(DonationFundService::class);

        $this->assertSame(125.0, $service->availableAmount($donation));

        $statement = $service->statement();

        $this->assertSame(200.0, $statement['income']);
        $this->assertSame(75.0, $statement['expense']);
        $this->assertSame(125.0, $statement['balance']);
        $this->assertCount(2, $statement['transactions']);
        $this->assertSame('Ana Maria · 01.12.2026', $statement['transactions'][0]['donation']);
        $this->assertSame('Legume și orez pentru masa de sâmbătă', $statement['transactions'][0]['description']);
        $this->assertSame(75.0, $statement['donations'][0]['spent']);
        $this->assertSame(125.0, $statement['donations'][0]['remaining']);

        $fund = Livewire::actingAs($coordinator)->test(CosturiBuget::class)->instance()->fund;

        $this->assertSame(125.0, $fund['balance']);
        $this->assertSame('Ana Maria · 01.12.2026', $fund['transactions'][0]['donation']);
    }

    public function test_a_coordinator_cannot_spend_more_than_the_donation_balance(): void
    {
        $congregation = Congregation::factory()->create();
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $this->actingAs($coordinator);

        $donation = $this->moneyDonation($congregation);

        Livewire::actingAs($coordinator)
            ->test(CreateFoodFundTransaction::class)
            ->fillForm([
                'type' => 'expense',
                'donation_id' => $donation->id,
                'transaction_date' => '2026-12-05',
                'amount' => 250,
                'category' => 'food',
                'description' => 'Cumpărături peste disponibil',
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertSame(0, FoodFundTransaction::query()->where('type', 'expense')->count());

        Livewire::actingAs($coordinator)
            ->test(CreateFoodFundTransaction::class)
            ->fillForm([
                'type' => 'expense',
                'donation_id' => $donation->id,
                'transaction_date' => '2026-12-05',
                'amount' => 150,
                'category' => 'food',
                'description' => 'Legume pentru masa de sâmbătă',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $expense = FoodFundTransaction::query()->where('type', 'expense')->firstOrFail();

        $this->assertSame($donation->id, (int) $expense->donation_id);
        $this->assertSame(50.0, app(DonationFundService::class)->availableAmount($donation));
    }

    public function test_deleting_a_donation_removes_its_income_but_keeps_the_expense_history(): void
    {
        $congregation = Congregation::factory()->create();
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        $this->actingAs($coordinator);

        $donation = $this->moneyDonation($congregation);
        $expense = FoodFundTransaction::query()->create([
            'type' => 'expense',
            'transaction_date' => '2026-12-05',
            'amount' => 75,
            'description' => 'Legume cumpărate din donație',
            'congregation_id' => $congregation->id,
            'donation_id' => $donation->id,
        ]);

        $donation->delete();

        $this->assertSame(0, FoodFundTransaction::query()->where('type', 'income')->count());
        $this->assertNull($expense->fresh()->donation_id);
        $this->assertSame('Legume cumpărate din donație', $expense->fresh()->description);
    }
}
