<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Donation;
use App\Models\Menu;
use App\Models\User;
use App\Models\Week;
use App\Services\DailyShoppingListCalculator;
use App\Services\WeeklyShoppingListCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationFoodDeductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_extended_donation_fields_are_persisted_and_linked(): void
    {
        $cooks = Congregation::factory()->create(['name' => 'Bucuresti Est']);
        $donor = Congregation::factory()->create(['name' => 'Bucuresti Titan']);

        $donation = Donation::create([
            'congregation_id' => $cooks->id,
            'donor_congregation_id' => $donor->id,
            'kind' => Donation::KIND_MONEY,
            'status' => Donation::STATUS_RECEIVED,
            'date' => '2026-12-07',
            'description' => 'Donație în bani',
            'amount_ron' => 120,
        ]);

        $this->assertSame('2026-12-07', $donation->date->toDateString());
        $this->assertSame('2026-12-07', $donation->donation_date->toDateString());
        $this->assertSame('120.00', (string) $donation->amount_ron);
        $this->assertSame('120.00', (string) $donation->estimated_value);
        $this->assertSame($donor->id, $donation->fresh()->donorCongregation->id);
    }

    public function test_only_received_food_matching_an_ingredient_is_deducted_from_shopping_lists(): void
    {
        $cooks = Congregation::factory()->create(['name' => 'Bucuresti Balta Alba']);
        $week = Week::factory()->for($cooks)->create([
            'week_number' => 49,
            'start_date' => '2026-12-07',
        ]);
        $menu = Menu::factory()->create([
            'ingredients' => [
                ['name' => 'Orez', 'quantity_per_person' => 1, 'unit' => 'kg', 'estimated_unit_cost' => 5],
            ],
            'packaging_cost' => 0,
        ]);
        $meal = DailyMeal::factory()->for($week)->for($cooks)->for($menu)->create([
            'meal_date' => '2026-12-07',
            'estimated_people' => 10,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $before = app(DailyShoppingListCalculator::class)->for($meal);
        $riceBefore = collect($before['ingredients'])->firstWhere('name', 'Orez');
        $toBuyBefore = (float) $riceBefore['to_buy_quantity'];
        $costBefore = (float) $riceBefore['estimated_cost'];

        $eligible = Donation::factory()->for($cooks)->create([
            'kind' => Donation::KIND_FOOD,
            'status' => Donation::STATUS_RECEIVED,
            'date' => '2026-12-07',
            'description' => 'Orez',
            'quantity' => 2,
            'unit' => 'kg',
            'amount_ron' => 10,
        ]);

        Donation::factory()->for($cooks)->create([
            'kind' => Donation::KIND_FOOD,
            'status' => Donation::STATUS_PROMISED,
            'date' => '2026-12-07',
            'description' => 'Orez',
            'quantity' => 5,
            'unit' => 'kg',
        ]);
        Donation::factory()->for($cooks)->create([
            'kind' => Donation::KIND_MONEY,
            'status' => Donation::STATUS_RECEIVED,
            'date' => '2026-12-07',
            'description' => 'Orez',
            'quantity' => 5,
            'unit' => 'kg',
        ]);
        Donation::factory()->for($cooks)->create([
            'kind' => Donation::KIND_FOOD,
            'status' => Donation::STATUS_USED,
            'date' => '2026-12-07',
            'description' => 'Orez',
            'quantity' => 5,
            'unit' => 'kg',
        ]);
        Donation::factory()->for($cooks)->create([
            'kind' => Donation::KIND_FOOD,
            'status' => Donation::STATUS_CANCELLED,
            'date' => '2026-12-07',
            'description' => 'Orez',
            'quantity' => 5,
            'unit' => 'kg',
        ]);
        Donation::factory()->for($cooks)->create([
            'kind' => Donation::KIND_FOOD,
            'status' => Donation::STATUS_RECEIVED,
            'date' => '2026-12-07',
            'description' => 'Cartofi inexistenti in reteta',
            'quantity' => 5,
            'unit' => 'kg',
        ]);

        $after = app(DailyShoppingListCalculator::class)->for($meal);
        $riceAfter = collect($after['ingredients'])->firstWhere('name', 'Orez');

        $this->assertSame(round($toBuyBefore - 2, 3), (float) $riceAfter['to_buy_quantity']);
        $this->assertSame(round($costBefore - 10, 2), (float) $riceAfter['estimated_cost']);
        $this->assertSame(2.0, (float) $riceAfter['donated_quantity']);
        $this->assertSame([$eligible->id], array_column($after['donation_deductions'], 'donation_id'));

        $weekly = app(WeeklyShoppingListCalculator::class)->calculate($week);
        $weeklyRice = collect($weekly['ingredients'])->firstWhere('name', 'Orez');

        $this->assertSame(round($toBuyBefore - 2, 3), (float) $weeklyRice['to_buy_quantity']);
        $this->assertSame([$eligible->id], array_column($weekly['donation_deductions'], 'donation_id'));
    }
}
