<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\PublicCongregationWeekLink;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCongregationWeekSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_public_link_shows_only_its_congregations_schedule_and_shopping_list(): void
    {
        $firstCongregation = Congregation::factory()->create(['name' => 'Congregatia Est', 'assistant_phone' => '0712345678']);
        $secondCongregation = Congregation::factory()->create(['name' => 'Congregatia Vest']);
        $week = Week::factory()->for($firstCongregation)->create();
        $firstMenu = Menu::factory()->create(['name' => 'Pilaf Est', 'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg']]]);
        $secondMenu = Menu::factory()->create(['name' => 'Tocana Vest', 'ingredients' => [['name' => 'Cartofi', 'quantity_per_person' => 0.2, 'unit' => 'kg']]]);
        DailyMeal::factory()->for($week)->for($firstCongregation)->for($firstMenu)->create(['estimated_people' => 20]);
        DailyMeal::factory()->for($week)->for($secondCongregation)->for($secondMenu)->create(['estimated_people' => 30]);
        $link = PublicCongregationWeekLink::create(['week_id' => $week->id, 'congregation_id' => $firstCongregation->id]);

        $this->get(route('public-congregation-week-sheets.show', $link))
            ->assertOk()
            ->assertSee('Congregatia Est')
            ->assertSee('Pilaf Est')
            ->assertSee('2 kg')
            ->assertDontSee('Congregatia Vest')
            ->assertDontSee('Tocana Vest')
            ->assertDontSee('Cartofi')
            ->assertDontSee('0712345678');
    }
}