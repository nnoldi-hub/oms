<?php

namespace Database\Factories;

use App\Models\DailyMeal;
use App\Models\Congregation;
use App\Models\Menu;
use App\Models\Week;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyMeal>
 */
class DailyMealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meal_date' => fake()->unique()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'week_id' => Week::factory(),
            'congregation_id' => Congregation::factory(),
            'menu_id' => Menu::factory(),
            'estimated_people' => fake()->numberBetween(1, 200),
            'notes' => fake()->optional()->sentence(),
            'status' => 'draft',
        ];
    }
}
