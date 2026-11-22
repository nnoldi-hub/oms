<?php

namespace Tests\Feature;

use App\Filament\Resources\Donations\DonationResource;
use App\Filament\Resources\Donations\Pages\CreateDonation;
use App\Filament\Resources\Donations\Pages\ListDonations;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Donation;
use App\Models\User;
use App\Models\Week;
use App\Services\DailyShoppingListCalculator;
use App\Services\DonationSummaryService;
use App\Services\WeeklyShoppingListCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DonationsTest extends TestCase
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

    public function test_a_coordinator_sees_and_manages_only_their_donations(): void
    {
        $own = Donation::factory()->for($this->own)->create(['estimated_value' => 100]);
        $other = Donation::factory()->for($this->other)->create(['estimated_value' => 900]);

        $this->actingAs($this->coordinator);

        $this->assertTrue(DonationResource::canAccess());
        $this->assertTrue(DonationResource::canEdit($own));
        $this->assertFalse(DonationResource::canEdit($other));

        Livewire::test(ListDonations::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);

        $summary = app(DonationSummaryService::class)->totals();

        $this->assertSame(100.0, $summary['total']);
    }

    public function test_a_coordinator_records_a_donation_only_for_their_congregation(): void
    {
        $this->actingAs($this->coordinator);

        Livewire::test(CreateDonation::class)
            ->fillForm([
                'status' => Donation::STATUS_RECEIVED,
                'donation_date' => '2026-12-01',
                'food_type' => 'legume',
                'description' => 'Legume',
                'estimated_value' => 50,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('donations', [
            'estimated_value' => 50,
            'congregation_id' => $this->own->id,
            'status' => Donation::STATUS_RECEIVED,
        ]);
    }

    public function test_a_coordinator_cannot_change_a_donation_of_another_congregation(): void
    {
        $other = Donation::factory()->for($this->other)->create();

        $this->actingAs($this->coordinator);

        $this->expectException(AuthorizationException::class);

        $other->update(['estimated_value' => 1]);
    }

    public function test_donation_totals_are_available_to_daily_and_weekly_shopping_lists(): void
    {
        $week = Week::factory()->create(['start_date' => '2026-12-07', 'congregation_id' => null]);
        $meal = DailyMeal::factory()->for($week)->for($this->own)->create(['meal_date' => '2026-12-07']);

        Donation::factory()->for($this->own)->create([
            'daily_meal_id' => $meal->id,
            'donation_date' => '2026-12-07',
            'status' => Donation::STATUS_RECEIVED,
            'estimated_value' => 25,
        ]);
        Donation::factory()->for($this->own)->create([
            'donation_date' => '2026-12-07',
            'status' => Donation::STATUS_PROMISED,
            'estimated_value' => 15,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $daily = app(DailyShoppingListCalculator::class)->for($meal);
        $this->assertSame(25.0, $daily['donations']['received']);
        $this->assertSame(15.0, $daily['donations']['promised']);
        $this->assertSame(40.0, $daily['donations']['total']);

        $weekly = app(WeeklyShoppingListCalculator::class)->calculate($week);
        $this->assertSame(25.0, $weekly['donations']['received']);
        $this->assertSame(15.0, $weekly['donations']['promised']);
        $this->assertSame(40.0, $weekly['donations']['total']);
    }
}
