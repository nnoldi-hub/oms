<?php

namespace Tests\Feature;

use App\Models\DailyMeal;
use App\Models\Menu;
use Carbon\CarbonImmutable;
use Database\Seeders\OmsScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FillMenusCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fills_only_draft_gaps_and_leaves_everything_else_intact(): void
    {
        $this->seed(OmsScheduleSeeder::class);

        $mainGap = $this->mealOn('2026-12-02');
        $soupedGap = $this->mealOn('2026-12-01');
        $monday = $this->mealOn('2026-11-30');
        $confirmed = $this->mealOn('2026-12-03');
        $untouched = $this->mealOn('2026-12-04');

        $mainGap->update(['menu_id' => null]);
        $soupedGap->update(['soup_menu_id' => null]);
        $monday->update(['soup_menu_id' => null]);
        $confirmed->update(['menu_id' => null, 'status' => 'confirmed']);

        $untouchedMenuId = $untouched->menu_id;
        $thursdaySoupId = $confirmed->soup_menu_id;

        $this->artisan('oms:fill-menus', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull($mainGap->fresh()->menu_id);
        $this->assertNull($soupedGap->fresh()->soup_menu_id);

        $this->artisan('oms:fill-menus')->assertSuccessful();

        $mainGap->refresh();
        $soupedGap->refresh();
        $monday->refresh();
        $confirmed->refresh();
        $untouched->refresh();

        $this->assertNotNull($mainGap->menu_id);
        $this->assertSame('main', $mainGap->menu->type);
        $this->assertNotNull($soupedGap->soup_menu_id);
        $this->assertSame($thursdaySoupId, $confirmed->soup_menu_id);
        $this->assertNotSame($soupedGap->soup_menu_id, $confirmed->soup_menu_id);
        $this->assertNull($monday->soup_menu_id);
        $this->assertNull($confirmed->menu_id);
        $this->assertSame($untouchedMenuId, $untouched->menu_id);
    }

    public function test_it_fills_a_schedule_that_was_emptied_without_breaking_the_rotation_rules(): void
    {
        $this->seed(OmsScheduleSeeder::class);
        DailyMeal::query()->update(['menu_id' => null, 'soup_menu_id' => null]);

        $this->artisan('oms:fill-menus')->assertSuccessful();

        $this->assertSame(0, DailyMeal::query()->whereNull('menu_id')->count());

        DailyMeal::query()->orderBy('meal_date')->get()->groupBy('week_id')
            ->each(function ($weekMeals): void {
                $soups = $weekMeals->whereNotNull('soup_menu_id')->values();

                $this->assertCount(2, $soups);
                $this->assertSame([2, 4], $soups->map(
                    fn (DailyMeal $meal) => CarbonImmutable::parse($meal->meal_date)->isoWeekday(),
                )->all());
                $this->assertNotSame($soups[0]->soup_menu_id, $soups[1]->soup_menu_id);
            });

        $mainDates = DailyMeal::query()->whereNotNull('menu_id')->orderBy('meal_date')->get()
            ->groupBy('menu_id')
            ->map(fn ($meals) => $meals->map(fn (DailyMeal $meal) => CarbonImmutable::parse($meal->meal_date)->toDateString()));

        $mainDates->each(function ($dates): void {
            foreach ($dates as $index => $date) {
                if ($index === 0) {
                    continue;
                }

                $distance = (int) CarbonImmutable::parse($dates[$index - 1])
                    ->diffInDays(CarbonImmutable::parse($date), absolute: true);

                $this->assertGreaterThanOrEqual(10, $distance);
            }
        });
    }

    public function test_it_reports_a_soup_scheduled_outside_the_second_and_fourth_meal(): void
    {
        $this->seed(OmsScheduleSeeder::class);

        $monday = $this->mealOn('2026-11-30');
        $soup = Menu::where('type', 'soup')->orderBy('id')->firstOrFail();

        DailyMeal::query()->whereKey($monday->id)->update(['soup_menu_id' => $soup->id]);

        $this->artisan('oms:fill-menus')
            ->expectsOutputToContain('a 2-a sau a 4-a masa')
            ->assertSuccessful();
    }

    public function test_a_second_run_does_not_reuse_a_recipe_within_ten_days(): void
    {
        $this->seed(OmsScheduleSeeder::class);

        $this->artisan('oms:fill-menus')->assertSuccessful();

        $meal = $this->mealOn('2026-12-04');
        $meal->update(['menu_id' => null]);

        $this->artisan('oms:fill-menus')->assertSuccessful();

        $this->assertSame(0, DailyMeal::query()->whereNull('menu_id')->count());

        $mainDates = DailyMeal::query()
            ->whereNotNull('menu_id')
            ->orderBy('meal_date')
            ->get()
            ->reject(fn (DailyMeal $entry): bool => (bool) $entry->is_free_day)
            ->groupBy('menu_id');

        $mainDates->each(function ($meals): void {
            $dates = $meals->map(fn (DailyMeal $meal) => CarbonImmutable::parse($meal->meal_date))->sort()->values();

            for ($index = 1; $index < $dates->count(); $index++) {
                $this->assertGreaterThanOrEqual(
                    10,
                    (int) $dates[$index - 1]->diffInDays($dates[$index], absolute: true),
                );
            }
        });
    }

    private function mealOn(string $date): DailyMeal
    {
        $meal = DailyMeal::query()
            ->get()
            ->first(fn (DailyMeal $candidate) => $candidate->meal_date->isSameDay($date));

        $this->assertNotNull($meal, 'Nu exista masa programata in '.$date);

        return $meal;
    }
}

