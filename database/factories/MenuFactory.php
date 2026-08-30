<?php

namespace Database\Factories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'instructions' => fake()->paragraph(),
            'ingredients' => [
                [
                    'name' => fake()->word(),
                    'quantity_per_person' => fake()->randomFloat(3, 0.01, 1),
                    'unit' => 'kg',
                    'estimated_unit_cost' => fake()->randomFloat(2, 1, 30),
                ],
            ],
            'allergens' => [],
            'packaging_cost' => fake()->randomFloat(2, 0, 10),
            'is_active' => true,
        ];
    }
}
