<?php

namespace Database\Factories;

use App\Models\Congregation;
use App\Support\CongregationRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Congregation>
 */
class CongregationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'type' => CongregationRole::CONTRIBUTES,
            'assistant_name' => fake()->name(),
            'assistant_phone' => fake()->phoneNumber(),
            'assistant_email' => fake()->safeEmail(),
        ];
    }
}
