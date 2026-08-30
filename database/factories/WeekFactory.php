<?php

namespace Database\Factories;

use App\Models\Congregation;
use App\Models\Week;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Week>
 */
class WeekFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'week_number' => fake()->unique()->numberBetween(1, 52),
            'start_date' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'congregation_id' => Congregation::factory(),
        ];
    }
}
