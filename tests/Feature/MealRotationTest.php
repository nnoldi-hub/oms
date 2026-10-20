<?php

namespace Tests\Feature;

use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Week;
use App\Models\Congregation;
use App\Services\MealRotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealRotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_order_counts_meals_chronologically_and_skips_free_days(): void
    {
        $week = Week::factory()->for(Congregation::factory())->create();

        $first = DailyMeal::factory()->for($week)->create(['meal_date' => '2026-11-30', 'menu_id' => null]);
        $free = DailyMeal::factory()->for($week)->create(['meal_date' => '2026-12-01', 'is_free_day' => true, 'menu_id' => null]);
        $second = DailyMeal::factory()->for($week)->create(['meal_date' => '2026-12-02', 'menu_id' => null]);
        $third = DailyMeal::factory()->for($week)->create(['meal_date' => '2026-12-03', 'menu_id' => null]);

        $order = app(MealRotation::class)->mainOrder(collect([$third, $first, $free, $second]));

        $this->assertSame(0, $order[$first->id]);
        $this->assertSame(1, $order[$second->id]);
        $this->assertSame(2, $order[$third->id]);
        $this->assertArrayNotHasKey($free->id, $order);
    }

    public function test_a_meal_with_a_manually_set_menu_does_not_consume_a_rotation_slot(): void
    {
        $week = Week::factory()->for(Congregation::factory())->create();
        $manualMenu = Menu::factory()->create(['type' => 'main']);

        $first = DailyMeal::factory()->for($week)->create(['meal_date' => '2026-11-30', 'menu_id' => null]);
        $manual = DailyMeal::factory()->for($week)->create(['meal_date' => '2026-12-01', 'menu_id' => $manualMenu->id]);
        $second = DailyMeal::factory()->for($week)->create(['meal_date' => '2026-12-02', 'menu_id' => null]);

        $order = app(MealRotation::class)->mainOrder(collect([$first, $manual, $second]));

        $this->assertSame(0, $order[$first->id]);
        $this->assertSame(1, $order[$second->id]);
        $this->assertArrayNotHasKey($manual->id, $order);
    }

    public function test_position_in_week_ignores_free_days_and_is_relative_to_the_week(): void
    {
        $rotation = app(MealRotation::class);
        $week = Week::factory()->for(Congregation::factory())->create();

        $meals = collect([
            DailyMeal::factory()->for($week)->create(['meal_date' => '2026-11-30']),
            DailyMeal::factory()->for($week)->create(['meal_date' => '2026-12-01', 'is_free_day' => true]),
            DailyMeal::factory()->for($week)->create(['meal_date' => '2026-12-02']),
            DailyMeal::factory()->for($week)->create(['meal_date' => '2026-12-03']),
        ]);

        $this->assertSame(0, $rotation->positionInWeek($meals, $meals[0]));
        $this->assertSame(1, $rotation->positionInWeek($meals, $meals[2]));
        $this->assertSame(2, $rotation->positionInWeek($meals, $meals[3]));
    }

    public function test_a_manual_menu_does_not_shift_the_filled_rotation(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();

        $menus = Menu::factory()->count(12)->create(['type' => 'main', 'is_active' => true]);
        Menu::factory()->count(5)->create(['type' => 'soup', 'is_active' => true]);
        $congregation->menus()->attach($menus);

        $meals = collect(range(0, 4))->map(fn (int $dayIndex): DailyMeal => DailyMeal::factory()->for($week)->for($congregation)->create([
            'meal_date' => \Carbon\CarbonImmutable::parse('2026-11-30')->addDays($dayIndex),
            'menu_id' => null,
            'soup_menu_id' => null,
        ]));

        $meals[1]->update(['menu_id' => $menus[7]->id]);

        $this->artisan('oms:fill-menus')->assertSuccessful();

        $ordered = DailyMeal::query()->orderBy('meal_date')->pluck('menu_id')->all();

        $this->assertSame($menus[7]->id, $ordered[1]);
        $this->assertSame($menus[0]->id, $ordered[0]);
        $this->assertSame($menus[1]->id, $ordered[2]);
        $this->assertSame($menus[2]->id, $ordered[3]);
        $this->assertSame($menus[3]->id, $ordered[4]);
    }

    public function test_the_soup_recipe_rotates_by_week_and_weekday_position(): void
    {
        $rotation = app(MealRotation::class);

        $this->assertSame(0, $rotation->soupRecipeIndex(5, 1, 1));
        $this->assertSame(1, $rotation->soupRecipeIndex(5, 1, 3));
        $this->assertSame(2, $rotation->soupRecipeIndex(5, 2, 1));
        $this->assertSame(3, $rotation->soupRecipeIndex(5, 2, 3));
        $this->assertSame(4, $rotation->soupRecipeIndex(5, 3, 1));
        $this->assertSame(0, $rotation->soupRecipeIndex(5, 3, 3));
        $this->assertNull($rotation->soupRecipeIndex(5, 1, 0));
        $this->assertNull($rotation->soupRecipeIndex(5, 1, 2));
        $this->assertNull($rotation->soupRecipeIndex(5, 1, 4));
    }

    public function test_the_main_course_follows_the_catalog_by_meal_order(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();

        $menus = Menu::factory()->count(12)->create(['type' => 'main', 'is_active' => true]);
        Menu::factory()->count(5)->create(['type' => 'soup', 'is_active' => true]);
        $congregation->menus()->attach($menus);

        foreach (range(0, 4) as $dayIndex) {
            DailyMeal::factory()->for($week)->for($congregation)->create([
                'meal_date' => \Carbon\CarbonImmutable::parse('2026-11-30')->addDays($dayIndex),
                'menu_id' => null,
                'soup_menu_id' => null,
            ]);
        }

        $this->artisan('oms:fill-menus')->assertSuccessful();

        $ordered = DailyMeal::query()->orderBy('meal_date')->pluck('menu_id')->all();

        $this->assertSame($menus->pluck('id')->take(5)->all(), $ordered);
    }
}
