<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\User;
use App\Models\Week;
use App\Services\DailyMealCostCalculator;
use App\Services\PeriodProgramPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PeriodProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_does_not_modify_anything(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['week_number' => 1, 'start_date' => '2026-11-02']);
        $menu = $this->mainMenu('Meniu existent');
        $existing = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create([
            'meal_date' => '2026-11-03',
        ]);

        $this->catalog();

        $preview = app(PeriodProgramPlanner::class)->preview($this->input('2026-11-09', '2026-11-13'));

        $this->assertNull($preview['error']);
        $this->assertSame(5, $preview['created_days']);
        $this->assertSame(1, DailyMeal::query()->count());
        $this->assertSame('2026-11-03', $existing->fresh()->meal_date->toDateString());
        $this->assertSame($menu->id, $existing->fresh()->menu_id);
    }

    public function test_replace_creates_the_working_days_of_the_period(): void
    {
        Congregation::factory()->create();
        $this->catalog();

        $result = app(PeriodProgramPlanner::class)->apply($this->input('2026-11-09', '2026-11-13'));

        $this->assertNull($result['error']);
        $this->assertSame(5, DailyMeal::query()->count());
        $this->assertSame(
            ['2026-11-09', '2026-11-10', '2026-11-11', '2026-11-12', '2026-11-13'],
            DailyMeal::query()->orderBy('meal_date')->get()->map(fn (DailyMeal $meal): string => $meal->meal_date->toDateString())->all(),
        );

        $week = Week::query()->sole();
        $this->assertSame(1, (int) $week->week_number);
        $this->assertSame('2026-11-09', $week->start_date->toDateString());

        $meals = DailyMeal::query()->orderBy('meal_date')->get()->keyBy(fn (DailyMeal $meal): string => $meal->meal_date->toDateString());
        $this->assertSame(2, $meals->filter(fn (DailyMeal $meal): bool => $meal->soup_menu_id !== null)->count());
        $this->assertNotNull($meals['2026-11-10']->soup_menu_id);
        $this->assertNotNull($meals['2026-11-12']->soup_menu_id);

        foreach ($meals as $meal) {
            $this->assertNotNull($meal->menu_id);
            $this->assertSame($week->id, $meal->week_id);
        }
    }

    public function test_protected_days_are_not_deleted_without_confirmation(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['week_number' => 1, 'start_date' => '2026-11-09']);
        $menu = $this->mainMenu('Meniu protejat');
        $protected = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create([
            'meal_date' => '2026-11-10',
            'confirmed_people' => 12,
        ]);

        $this->catalog();

        $preview = app(PeriodProgramPlanner::class)->preview($this->input('2026-11-09', '2026-11-13'));

        $this->assertNull($preview['error']);
        $this->assertCount(1, $preview['protected']);
        $this->assertSame('2026-11-10', $preview['protected'][0]['date']);
        $this->assertFalse($preview['protected'][0]['will_delete']);

        app(PeriodProgramPlanner::class)->apply($this->input('2026-11-09', '2026-11-13'));

        $this->assertDatabaseHas('daily_meals', ['id' => $protected->id, 'confirmed_people' => 12]);
        $this->assertSame(5, DailyMeal::query()->count());

        $input = $this->input('2026-11-09', '2026-11-13');
        $input['delete_protected'] = true;

        app(PeriodProgramPlanner::class)->apply($input);

        $this->assertDatabaseMissing('daily_meals', ['id' => $protected->id]);
        $this->assertSame(5, DailyMeal::query()->count());
    }

    public function test_adjust_only_changes_the_ends_of_the_period(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['week_number' => 2, 'start_date' => '2026-11-09']);
        $earlierWeek = Week::factory()->for($congregation)->create(['week_number' => 1, 'start_date' => '2026-11-02']);
        $menu = $this->mainMenu('Meniu pastrat');

        $protectedBefore = DailyMeal::factory()->for($earlierWeek)->for($congregation)->for($menu)->create([
            'meal_date' => '2026-11-06',
            'confirmed_people' => 5,
        ]);

        foreach (['2026-11-09', '2026-11-10', '2026-11-11', '2026-11-12', '2026-11-13'] as $date) {
            DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create([
                'meal_date' => $date,
                'estimated_people' => 20,
            ]);
        }

        $ids = DailyMeal::query()->get()->mapWithKeys(fn (DailyMeal $meal): array => [
            $meal->meal_date->toDateString() => $meal->id,
        ])->all();
        $this->catalog();

        $input = $this->input('2026-11-11', '2026-11-17');
        $input['mode'] = 'adjust';
        $input['reuse_menus'] = true;

        $result = app(PeriodProgramPlanner::class)->apply($input);

        $this->assertNull($result['error']);
        $this->assertDatabaseHas('daily_meals', ['id' => $protectedBefore->id, 'confirmed_people' => 5]);
        $this->assertSame(
            ['2026-11-06', '2026-11-11', '2026-11-12', '2026-11-13', '2026-11-16', '2026-11-17'],
            DailyMeal::query()->orderBy('meal_date')->get()->map(fn (DailyMeal $meal): string => $meal->meal_date->toDateString())->all(),
        );
        $this->assertDatabaseMissing('daily_meals', ['meal_date' => '2026-11-09']);
        $this->assertDatabaseMissing('daily_meals', ['meal_date' => '2026-11-10']);

        foreach (['2026-11-11', '2026-11-12', '2026-11-13'] as $date) {
            $this->assertDatabaseHas('daily_meals', [
                'id' => $ids[$date],
                'menu_id' => $menu->id,
                'estimated_people' => 20,
            ]);
        }
    }

    public function test_free_days_are_created_without_a_menu(): void
    {
        Congregation::factory()->create();
        $this->catalog();

        $input = $this->input('2026-11-30', '2026-12-04');
        $input['holidays'] = ['2026-12-01'];

        $result = app(PeriodProgramPlanner::class)->apply($input);

        $this->assertNull($result['error']);
        $this->assertSame(5, DailyMeal::query()->count());

        $freeDay = DailyMeal::query()->whereDate('meal_date', '2026-12-01')->sole();
        $this->assertTrue($freeDay->is_free_day);
        $this->assertNull($freeDay->menu_id);
        $this->assertNull($freeDay->soup_menu_id);

        $this->assertSame(4, DailyMeal::query()->where('is_free_day', false)->count());
    }

    public function test_an_error_inside_the_transaction_leaves_no_partial_data(): void
    {
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create(['week_number' => 1, 'start_date' => '2026-11-02']);
        $menu = $this->mainMenu('Meniu initial');
        $existing = DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create([
            'meal_date' => '2026-11-03',
        ]);

        $this->catalog();

        $planner = new class(app(DailyMealCostCalculator::class)) extends PeriodProgramPlanner
        {
            protected function afterStructureApplied(): void
            {
                throw new \RuntimeException('boom');
            }
        };

        try {
            $planner->apply($this->input('2026-11-09', '2026-11-13'));
            $this->fail('Aplicarea ar fi trebuit sa arunce exceptia.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        $this->assertDatabaseHas('daily_meals', ['id' => $existing->id]);
        $this->assertSame(1, DailyMeal::query()->count());
        $this->assertSame(1, Week::query()->count());
        $this->assertSame(0, DailyMeal::query()->whereDate('meal_date', '>=', '2026-11-09')->count());
    }

    public function test_a_coordinator_cannot_open_the_program_period(): void
    {
        $congregation = Congregation::factory()->create();
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        Livewire::actingAs($coordinator)
            ->test(CalendarSaptamanal::class)
            ->call('openProgramPeriod')
            ->assertForbidden();
    }

    /**
     * @return array{from: string, to: string, holidays: list<string>, include_saturday: bool, mode: string, reuse_menus: bool, delete_protected: bool}
     */
    private function input(string $from, string $to): array
    {
        return [
            'from' => $from,
            'to' => $to,
            'holidays' => [],
            'include_saturday' => false,
            'mode' => 'replace',
            'reuse_menus' => true,
            'delete_protected' => false,
        ];
    }

    private function catalog(int $mains = 8, int $soups = 4): void
    {
        for ($index = 1; $index <= $mains; $index++) {
            $this->mainMenu("Fel principal {$index}", 8 + $index);
        }

        for ($index = 1; $index <= $soups; $index++) {
            Menu::factory()->create([
                'name' => "Ciorba {$index}",
                'type' => 'soup',
                'ingredients' => [],
                'cost_per_portion' => 3 + $index,
            ]);
        }
    }

    private function mainMenu(string $name, float $cost = 10.0): Menu
    {
        return Menu::factory()->create([
            'name' => $name,
            'type' => 'main',
            'ingredients' => [],
            'cost_per_portion' => $cost,
        ]);
    }
}
