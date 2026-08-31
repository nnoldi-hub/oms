<?php

namespace Database\Factories;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ingredient> */
class IngredientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'unit' => 'kg',
            'unit_price' => fake()->randomFloat(2, 1, 30),
            'is_active' => true,
        ];
    }
}