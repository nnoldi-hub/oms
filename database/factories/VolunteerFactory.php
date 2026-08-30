<?php

namespace Database\Factories;

use App\Models\Volunteer;
use App\Models\DailyMeal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Volunteer>
 */
class VolunteerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'daily_meal_id' => DailyMeal::factory(),
            'name' => fake()->name(),
            'phone' => fake()->optional()->phoneNumber(),
            'role' => 'preparare',
            'has_allergies' => false,
            'allergy_details' => null,
        ];
    }
}
