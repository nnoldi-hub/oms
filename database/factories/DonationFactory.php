<?php

namespace Database\Factories;

use App\Models\Congregation;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'congregation_id' => Congregation::factory(),
            'status' => Donation::STATUS_PROMISED,
            'donation_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'food_type' => 'legume',
            'description' => fake()->words(3, true),
            'quantity' => fake()->randomFloat(2, 1, 50),
            'unit' => 'kg',
            'estimated_value' => fake()->randomFloat(2, 10, 500),
            'donor_name' => fake()->optional()->name(),
            'donor_phone' => fake()->optional()->phoneNumber(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Donation::STATUS_RECEIVED,
        ]);
    }
}
