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

    public function test_a_public_link_shows_only_its_congregations_schedule_without_prices_or_contacts(): void
    {
        $firstCongregation = Congregation::factory()->create([
            'name' => 'Congregatia Est',
            'assistant_name' => 'Contact secret',
            'assistant_phone' => 'TELEFON_SECRET',
        ]);
        $secondCongregation = Congregation::factory()->create(['name' => 'Congregatia Vest']);
        $week = Week::factory()->for($firstCongregation)->create();
        $firstMenu = Menu::factory()->create([
            'name' => 'Pilaf Est',
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 987.65]],
        ]);
        $secondMenu = Menu::factory()->create(['name' => 'Tocana Vest', 'ingredients' => [['name' => 'Cartofi', 'quantity_per_person' => 0.2, 'unit' => 'kg']]]);
        DailyMeal::factory()->for($week)->for($firstCongregation)->for($firstMenu)->create([
            'estimated_people' => 20,
            'maximum_budget' => 654.32,
            'actual_cost' => 321.98,
        ]);
        DailyMeal::factory()->for($week)->for($secondCongregation)->for($secondMenu)->create(['estimated_people' => 30]);
        $link = PublicCongregationWeekLink::create(['week_id' => $week->id, 'congregation_id' => $firstCongregation->id]);

        $this->get(route('public-congregation-week-sheets.show', $link))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('noindex, nofollow', false)
            ->assertSee('Congregatia Est')
            ->assertSee('Pilaf Est')
            ->assertSee('2 kg')
            ->assertDontSee('Congregatia Vest')
            ->assertDontSee('Tocana Vest')
            ->assertDontSee('Cartofi')
            ->assertDontSee('Contact secret')
            ->assertDontSee('TELEFON_SECRET')
            ->assertDontSee('987.65')
            ->assertDontSee('654.32')
            ->assertDontSee('321.98')
            ->assertDontSee('RON');
    }
}
