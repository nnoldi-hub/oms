<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Week;
use App\Services\MenuSchedulingValidator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MenuSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private function validator(): MenuSchedulingValidator
    {
        return app(MenuSchedulingValidator::class);
    }

    public function test_a_main_recipe_used_within_ten_days_saves_but_produces_a_warning(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $mainMenu = Menu::factory()->create(['type' => 'main']);
        $congregation->menus()->attach($mainMenu);

        DailyMeal::factory()->for($week)->for($congregation)->for($mainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-04'),
        ]);

        $tooSoonMeal = DailyMeal::factory()->for($week)->for($congregation)->for($mainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-08'),
        ]);

        $warnings = $this->validator()->warnings($tooSoonMeal);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('minimum 10 zile', $warnings[0]);
    }

    public function test_a_main_recipe_used_after_ten_days_produces_no_warning(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $mainMenu = Menu::factory()->create(['type' => 'main']);
        $congregation->menus()->attach($mainMenu);

        DailyMeal::factory()->for($week)->for($congregation)->for($mainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-04'),
        ]);
        $laterMeal = DailyMeal::factory()->for($week)->for($congregation)->for($mainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-14'),
        ]);

        $this->assertSame([], $this->validator()->warnings($laterMeal));
    }

    public function test_selecting_a_soup_as_the_main_course_is_blocked(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $soupMenu = Menu::factory()->create(['type' => 'soup']);
        $congregation->menus()->attach($soupMenu);

        $this->expectException(ValidationException::class);

        DailyMeal::factory()->for($week)->for($congregation)->for($soupMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-04'),
        ]);
    }

    public function test_an_inactive_recipe_is_blocked(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $mainMenu = Menu::factory()->create(['type' => 'main', 'is_active' => false]);
        $congregation->menus()->attach($mainMenu);

        $this->expectException(ValidationException::class);

        DailyMeal::factory()->for($week)->for($congregation)->for($mainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-04'),
        ]);
    }

    public function test_two_soups_are_allowed_in_a_week_but_a_third_one_is_blocked(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $firstMainMenu = Menu::factory()->create(['type' => 'main']);
        $secondMainMenu = Menu::factory()->create(['type' => 'main']);
        $thirdMainMenu = Menu::factory()->create(['type' => 'main']);
        $firstSoup = Menu::factory()->create(['type' => 'soup']);
        $secondSoup = Menu::factory()->create(['type' => 'soup']);
        $thirdSoup = Menu::factory()->create(['type' => 'soup']);
        $congregation->menus()->attach([
            $firstMainMenu->id,
            $secondMainMenu->id,
            $thirdMainMenu->id,
            $firstSoup->id,
            $secondSoup->id,
            $thirdSoup->id,
        ]);

        DailyMeal::factory()->for($week)->for($congregation)->for($firstMainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-01'),
            'soup_menu_id' => $firstSoup->id,
        ]);
        DailyMeal::factory()->for($week)->for($congregation)->for($secondMainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-03'),
            'soup_menu_id' => $secondSoup->id,
        ]);

        $this->expectException(ValidationException::class);

        DailyMeal::factory()->for($week)->for($congregation)->for($thirdMainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-04'),
            'soup_menu_id' => $thirdSoup->id,
        ]);
    }

    public function test_a_soup_scheduled_outside_tuesday_and_thursday_produces_a_warning(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $mainMenu = Menu::factory()->create(['type' => 'main']);
        $soupMenu = Menu::factory()->create(['type' => 'soup']);
        $congregation->menus()->attach([$mainMenu->id, $soupMenu->id]);

        $meal = DailyMeal::factory()->for($week)->for($congregation)->for($mainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-04'),
            'soup_menu_id' => $soupMenu->id,
        ]);

        $this->assertSame(
            ['Ciorba este recomandata in zilele de marti si joi.'],
            $this->validator()->warnings($meal),
        );
    }

    public function test_the_same_soup_twice_in_a_week_produces_a_warning(): void
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
        $secondMeal = DailyMeal::factory()->for($week)->for($congregation)->for($secondMainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-03'),
            'soup_menu_id' => $soupMenu->id,
        ]);

        $this->assertStringContainsString(
            'de doua ori in aceeasi saptamana',
            implode(' ', $this->validator()->warnings($secondMeal)),
        );
    }

    public function test_the_same_soup_in_consecutive_meals_produces_a_warning(): void
    {
        $congregation = Congregation::factory()->create();
        $firstWeek = Week::factory()->for($congregation)->create();
        $secondWeek = Week::factory()->for($congregation)->create();
        $firstMainMenu = Menu::factory()->create(['type' => 'main']);
        $secondMainMenu = Menu::factory()->create(['type' => 'main']);
        $soupMenu = Menu::factory()->create(['type' => 'soup']);
        $congregation->menus()->attach([$firstMainMenu->id, $secondMainMenu->id, $soupMenu->id]);

        DailyMeal::factory()->for($firstWeek)->for($congregation)->for($firstMainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-01'),
            'soup_menu_id' => $soupMenu->id,
        ]);
        $secondMeal = DailyMeal::factory()->for($secondWeek)->for($congregation)->for($secondMainMenu)->create([
            'meal_date' => CarbonImmutable::parse('2026-12-15'),
            'soup_menu_id' => $soupMenu->id,
        ]);

        $this->assertStringContainsString(
            'alternarea ciorbelor',
            implode(' ', $this->validator()->warnings($secondMeal)),
        );
    }
}
