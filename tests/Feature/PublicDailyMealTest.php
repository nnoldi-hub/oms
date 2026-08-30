<?php

namespace Tests\Feature;

use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Volunteer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDailyMealTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_meal_shows_its_requirements_without_personal_data(): void
    {
        $menu = Menu::factory()->create([
            'name' => 'Tocanita',
            'instructions' => 'Se serveste calda.',
            'ingredients' => [['name' => 'Cartofi', 'quantity_per_person' => 0.2, 'unit' => 'kg']],
        ]);
        $meal = DailyMeal::factory()->for($menu)->create([
            'estimated_people' => 30,
            'status' => 'published',
        ]);
        Volunteer::factory()->for($meal)->create([
            'name' => 'Nume Privat',
            'phone' => '0712345678',
            'has_allergies' => true,
            'allergy_details' => 'Nuci',
        ]);

        $this->get(route('public-daily-meals.show', $meal))
            ->assertOk()
            ->assertSee('Tocanita')
            ->assertSee('6 kg')
            ->assertSee('30 caserole')
            ->assertDontSee('Nume Privat')
            ->assertDontSee('0712345678')
            ->assertDontSee('Nuci');
    }

    public function test_an_unpublished_meal_is_not_available_through_its_public_token(): void
    {
        $meal = DailyMeal::factory()->create(['status' => 'draft']);

        $this->get(route('public-daily-meals.show', $meal))->assertNotFound();
    }
}