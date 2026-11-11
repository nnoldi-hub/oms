<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Week;
use App\Services\MenuUsageInsight;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuUsageInsightTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_the_last_usage_the_window_count_and_the_next_scheduled_day_for_all_congregations(): void
    {
        $menu = Menu::factory()->create(['name' => 'Pilaf cu pui']);
        $titan = Congregation::factory()->create(['name' => 'Titan']);
        $balta = Congregation::factory()->create(['name' => 'Balta Alba']);
        $cedra = Congregation::factory()->create(['name' => 'Cedra']);
        $replannedMenu = Menu::factory()->create(['name' => 'Alt fel planificat']);

        $this->meal($titan, '2026-11-12', ['menu_id' => $menu->id]);
        $this->meal($balta, '2026-11-05', ['menu_id' => $replannedMenu->id, 'is_cooked' => true, 'actual_menu_id' => $menu->id]);
        $this->meal($cedra, '2026-10-20', ['menu_id' => $menu->id]);
        $this->meal($titan, '2026-11-25', ['menu_id' => $menu->id]);
        $this->meal($titan, '2026-09-01', ['menu_id' => $menu->id]);

        $editedDay = $this->meal($titan, '2026-11-18', ['menu_id' => $menu->id]);

        $insight = app(MenuUsageInsight::class)->insight($menu, CarbonImmutable::parse('2026-11-18'), $editedDay);

        $this->assertSame([
            'date' => '2026-11-12',
            'congregation' => 'Titan',
            'days_passed' => 6,
        ], $insight['last_used']);
        $this->assertSame(3, $insight['used_in_window']);
        $this->assertSame([
            'date' => '2026-11-25',
            'congregation' => 'Titan',
        ], $insight['next_scheduled']);
    }

    public function test_it_counts_soups_and_desserts_from_their_own_fields(): void
    {
        $soup = Menu::factory()->create(['type' => 'soup']);
        $dessert = Menu::factory()->create(['type' => 'dessert']);
        $titan = Congregation::factory()->create(['name' => 'Titan']);
        $balta = Congregation::factory()->create(['name' => 'Balta Alba']);
        $main = Menu::factory()->create();

        $this->meal($titan, '2026-11-03', ['menu_id' => $main->id, 'soup_menu_id' => $soup->id]);
        $this->meal($balta, '2026-11-10', ['menu_id' => $main->id, 'soup_menu_id' => $soup->id, 'dessert_menu_id' => $dessert->id]);
        $editedDay = $this->meal($titan, '2026-11-18', ['menu_id' => $main->id, 'soup_menu_id' => $soup->id]);

        $soupInsight = app(MenuUsageInsight::class)->insight($soup, '2026-11-18', $editedDay);
        $dessertInsight = app(MenuUsageInsight::class)->insight($dessert, '2026-11-18', $editedDay);

        $this->assertSame(2, $soupInsight['used_in_window']);
        $this->assertSame('2026-11-10', $soupInsight['last_used']['date']);
        $this->assertSame('Balta Alba', $soupInsight['last_used']['congregation']);
        $this->assertSame(1, $dessertInsight['used_in_window']);
        $this->assertSame('Balta Alba', $dessertInsight['last_used']['congregation']);
    }

    public function test_it_ignores_free_days(): void
    {
        $menu = Menu::factory()->create();
        $titan = Congregation::factory()->create(['name' => 'Titan']);

        $this->meal($titan, '2026-11-12', ['menu_id' => $menu->id, 'is_free_day' => true]);
        $this->meal($titan, '2026-11-10', ['menu_id' => $menu->id, 'is_free_day' => true, 'is_cooked' => true, 'actual_menu_id' => $menu->id]);
        $editedDay = $this->meal($titan, '2026-11-18', ['menu_id' => null]);

        $insight = app(MenuUsageInsight::class)->insight($menu, '2026-11-18', $editedDay);

        $this->assertNull($insight['last_used']);
        $this->assertSame(0, $insight['used_in_window']);
        $this->assertNull($insight['next_scheduled']);
    }

    public function test_it_excludes_the_currently_edited_day(): void
    {
        $menu = Menu::factory()->create();
        $titan = Congregation::factory()->create(['name' => 'Titan']);

        $editedDay = $this->meal($titan, '2026-11-18', ['menu_id' => $menu->id]);

        $insight = app(MenuUsageInsight::class)->insight($menu, '2026-11-18', $editedDay);

        $this->assertNull($insight['last_used']);
        $this->assertSame(0, $insight['used_in_window']);
        $this->assertNull($insight['next_scheduled']);
    }

    public function test_a_never_used_menu_has_no_insight_data(): void
    {
        $menu = Menu::factory()->create();
        $titan = Congregation::factory()->create(['name' => 'Titan']);
        $editedDay = $this->meal($titan, '2026-11-18', ['menu_id' => null]);

        $insight = app(MenuUsageInsight::class)->insight($menu, '2026-11-18', $editedDay);

        $this->assertNull($insight['last_used']);
        $this->assertSame(0, $insight['used_in_window']);
        $this->assertNull($insight['next_scheduled']);
    }

    public function test_the_thresholds_are_defined_in_one_place(): void
    {
        $this->assertSame(14, MenuUsageInsight::RECENT_DAYS);
        $this->assertSame(3, MenuUsageInsight::OFTEN_COOKED_COUNT);
        $this->assertSame(6, MenuUsageInsight::USAGE_WINDOW_WEEKS);
    }

    private function meal(Congregation $congregation, string $date, array $attributes = []): DailyMeal
    {
        $week = Week::factory()->for($congregation)->create();

        return DailyMeal::factory()
            ->for($week)
            ->for($congregation)
            ->create(array_merge(['meal_date' => $date, 'estimated_people' => 10], $attributes));
    }
}
