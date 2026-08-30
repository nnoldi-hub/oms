<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Volunteer;
use App\Models\Week;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OmsDataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_oms_models_store_and_expose_their_relationships(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $menu = Menu::factory()->create();
        $meal = DailyMeal::factory()->for($week)->for($menu)->create();
        $volunteer = Volunteer::factory()->for($meal)->create([
            'has_allergies' => true,
            'allergy_details' => 'Nuci',
        ]);

        $meal->load('week.congregation', 'menu', 'volunteers');

        $this->assertSame($congregation->id, $meal->week->congregation->id);
        $this->assertSame($menu->id, $meal->menu->id);
        $this->assertTrue($volunteer->has_allergies);
        $this->assertSame('Nuci', $meal->volunteers->sole()->allergy_details);
        $this->assertIsArray($meal->menu->ingredients);
    }

    public function test_a_meal_date_can_only_be_scheduled_once(): void
    {
        $meal = DailyMeal::factory()->create();

        $this->expectException(QueryException::class);

        DailyMeal::factory()->create(['meal_date' => $meal->meal_date]);
    }

    public function test_allergy_details_are_required_when_a_volunteer_has_allergies(): void
    {
        $this->expectException(ValidationException::class);

        Volunteer::factory()->create([
            'has_allergies' => true,
            'allergy_details' => null,
        ]);
    }
}