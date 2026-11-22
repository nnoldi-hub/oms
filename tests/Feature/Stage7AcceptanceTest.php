<?php

namespace Tests\Feature;

use App\Filament\Resources\DailyMeals\DailyMealResource;
use App\Filament\Resources\Donations\DonationResource;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\Week;
use App\Support\CongregationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class Stage7AcceptanceTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_contributors_have_only_their_own_non_financial_access(): void
    {
        [$cooks, $mine, $other] = Congregation::factory()->count(3)->create();
        $week = Week::factory()->for($cooks)->create();
        $menu = Menu::factory()->create(['name' => 'Meniu cu cost']);
        $myDay = DailyMeal::factory()->for($week)->for($cooks)->for($menu)->create([
            'meal_date' => '2026-12-07',
            'maximum_budget' => 500,
        ]);
        $otherDay = DailyMeal::factory()->for($week)->for($cooks)->create(['meal_date' => '2026-12-08']);
        $myDay->syncContributingCongregations([$mine->id, $other->id]);

        $contributor = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $mine->id]);
        $this->actingAs($contributor);

        $this->assertTrue(CongregationAccess::canSeeMeal($contributor, $myDay));
        $this->assertFalse(CongregationAccess::canSeeMeal($contributor, $otherDay));
        $this->assertFalse(CongregationAccess::canManageMeal($contributor, $myDay));
        $this->assertFalse(Gate::allows('viewFull', $myDay));
        $this->assertFalse(DailyMealResource::canViewSensitiveFields($myDay));
        $this->assertFalse(DailyMealResource::canEdit($myDay));
        $this->assertFalse(DonationResource::canAccess());
    }

    public function test_the_cooking_congregation_is_never_added_as_a_contributor(): void
    {
        $cooks = Congregation::factory()->create();
        $first = Congregation::factory()->create();
        $second = Congregation::factory()->create();
        $third = Congregation::factory()->create();
        $week = Week::factory()->for($cooks)->create();
        $meal = DailyMeal::factory()->for($week)->for($cooks)->create(['meal_date' => '2026-12-07']);

        $meal->syncContributingCongregations([$cooks->id, $first->id, $second->id, $third->id]);

        $this->assertSame([$first->id, $second->id, $third->id], $meal->fresh()->contributingCongregationIds());
        $this->assertSame($cooks->id, $meal->fresh()->congregation_id);
    }

    public function test_import_preserves_cooking_congregation_menu_people_volunteers_and_costs(): void
    {
        $cooks = Congregation::factory()->create(['name' => 'Bucuresti Est']);
        $oldContributor = Congregation::factory()->create(['name' => 'Contribuitor vechi']);
        $week = Week::factory()->for($cooks)->create([
            'week_number' => 1,
            'start_date' => '2026-12-07',
        ]);
        $menu = Menu::factory()->create(['name' => 'Meniu pastrat']);
        $meal = DailyMeal::factory()->for($week)->for($cooks)->for($menu)->create([
            'meal_date' => '2026-12-07',
            'estimated_people' => 42,
            'confirmed_people' => 40,
            'maximum_budget' => 600,
            'actual_cost' => 555.50,
            'notes' => 'Note bucatar',
        ]);
        $meal->syncContributingCongregations([$oldContributor->id]);
        Volunteer::factory()->for($meal)->create(['phone' => null]);

        $path = $this->writeCsv(<<<'CSV'
        saptamana,data,congregatia_1,congregatia_2,congregatia_3,nota
        1,2026-12-07,Contribuitor nou A,Contribuitor nou B,Contribuitor nou C,Nota program
        CSV);

        $this->artisan('oms:import-program', ['file' => $path, '--apply' => true])->assertSuccessful();

        $fresh = $meal->fresh();
        $this->assertSame($cooks->id, $fresh->congregation_id);
        $this->assertSame($menu->id, $fresh->menu_id);
        $this->assertSame(42, $fresh->estimated_people);
        $this->assertSame(40, $fresh->confirmed_people);
        $this->assertSame('600.00', (string) $fresh->maximum_budget);
        $this->assertSame('555.50', (string) $fresh->actual_cost);
        $this->assertSame('Note bucatar', $fresh->notes);
        $this->assertSame('Nota program', $fresh->contribution_notes);
        $this->assertSame(1, $fresh->volunteers()->count());
        $this->assertSame(
            ['Contribuitor nou A', 'Contribuitor nou B', 'Contribuitor nou C'],
            $fresh->contributingCongregations->pluck('name')->all(),
        );
    }

    public function test_import_creates_saturday_days_with_the_week_cooking_congregation(): void
    {
        $cooks = Congregation::factory()->create(['name' => 'Bucuresti Titan']);
        $week = Week::factory()->for($cooks)->create([
            'week_number' => 2,
            'start_date' => '2026-12-07',
            'include_saturday' => true,
        ]);

        $path = $this->writeCsv(<<<'CSV'
        saptamana,data,congregatia_1,congregatia_2,congregatia_3,nota
        2,2026-12-12,Contribuitor A,Contribuitor B,Contribuitor C,
        CSV);

        $this->artisan('oms:import-program', ['file' => $path, '--apply' => true])->assertSuccessful();

        $saturday = DailyMeal::query()->whereDate('meal_date', '2026-12-12')->sole();
        $this->assertSame($cooks->id, $saturday->congregation_id);
        $this->assertSame($week->id, $saturday->week_id);
        $this->assertSame($cooks->id, $week->fresh()->congregation_id);
    }

    public function test_a_tuesday_start_csv_uses_the_iso_monday_without_conflicts(): void
    {
        $cooks = Congregation::factory()->create(['name' => 'Bucuresti Balta Alba']);
        $week = Week::factory()->for($cooks)->create([
            'week_number' => 3,
            'start_date' => '2026-12-14',
        ]);

        $path = $this->writeCsv(<<<'CSV'
        saptamana,data,congregatia_1,congregatia_2,congregatia_3,nota
        3,2026-12-15,Contribuitor A,Contribuitor B,Contribuitor C,
        3,2026-12-16,Contribuitor A,Contribuitor B,Contribuitor C,
        CSV);

        $this->artisan('oms:import-program', ['file' => $path, '--apply' => true])
            ->doesntExpectOutputToContain('Conflicte fata de baza de date')
            ->assertSuccessful();

        $this->assertSame('2026-12-14', $week->fresh()->start_date->toDateString());
        $this->assertSame($cooks->id, $week->fresh()->congregation_id);
        $this->assertSame(2, DailyMeal::count());
        $this->assertSame(0, DailyMeal::query()->whereDate('meal_date', '2026-12-14')->count());
    }

    private function writeCsv(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'stage7_');
        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
