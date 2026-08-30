<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Week;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MenuSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_main_recipe_can_only_be_scheduled_again_after_six_days(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $mainMenu = Menu::factory()->create(['type' => 'main']);
        $secondMainMenu = Menu::factory()->create(['type' => 'main']);
        $congregation->menus()->attach([$mainMenu->id, $secondMainMenu->id]);

        DailyMeal::factory()->for($week)->for($congregation)->for($mainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-04'),
        ]);

        $this->expectException(ValidationException::class);

        DailyMeal::factory()->for($week)->for($congregation)->for($mainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-08'),
        ]);
    }

    public function test_a_main_recipe_can_be_scheduled_again_six_days_later(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $mainMenu = Menu::factory()->create(['type' => 'main']);
        $congregation->menus()->attach($mainMenu);

        DailyMeal::factory()->for($week)->for($congregation)->for($mainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-04'),
        ]);
        $laterMeal = DailyMeal::factory()->for($week)->for($congregation)->for($mainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-10'),
        ]);

        $this->assertSame('2026-12-10', CarbonImmutable::parse($laterMeal->meal_date)->toDateString());
    }

    public function test_only_one_soup_can_be_scheduled_per_week(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $firstMainMenu = Menu::factory()->create(['type' => 'main']);
        $secondMainMenu = Menu::factory()->create(['type' => 'main']);
        $soupMenu = Menu::factory()->create(['type' => 'soup']);
        $congregation->menus()->attach([$firstMainMenu->id, $secondMainMenu->id, $soupMenu->id]);

        DailyMeal::factory()->for($week)->for($congregation)->for($firstMainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-01'),
            'soup_menu_id' => $soupMenu->id,
        ]);

        $this->expectException(ValidationException::class);

        DailyMeal::factory()->for($week)->for($congregation)->for($secondMainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-02'),
            'soup_menu_id' => $soupMenu->id,
        ]);
    }
}