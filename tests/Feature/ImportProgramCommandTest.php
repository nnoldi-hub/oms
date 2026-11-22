<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Volunteer;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportProgramCommandTest extends TestCase
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

    public function test_dry_run_is_the_default_and_does_not_change_the_database(): void
    {
        $path = $this->writeCsv($this->smallProgram());

        $this->artisan('oms:import-program', ['file' => $path])
            ->expectsOutputToContain('Mod de testare (implicit): nu s-a modificat nimic.')
            ->expectsOutputToContain('Congregatii de creat')
            ->expectsOutputToContain('Zile din CSV absente in baza de date')
            ->assertSuccessful();

        $this->assertSame(0, Congregation::count());
        $this->assertSame(0, Week::count());
        $this->assertSame(0, DailyMeal::count());
    }

    public function test_apply_creates_the_exact_program_and_a_second_run_changes_nothing(): void
    {
        $path = $this->writeCsv($this->smallProgram());

        $this->artisan('oms:import-program', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('Program importat:')
            ->assertSuccessful();

        $this->assertSame(5, Congregation::count());
        $this->assertSame(2, Week::count());
        $this->assertSame(6, DailyMeal::count());

        $firstWeek = Week::query()->where('week_number', 1)->firstOrFail();
        $secondWeek = Week::query()->where('week_number', 2)->firstOrFail();

        $this->assertSame('2026-12-07', $firstWeek->start_date->toDateString());
        $this->assertFalse($firstWeek->include_saturday);
        $this->assertNull($firstWeek->congregation_id);
        $this->assertTrue($secondWeek->include_saturday);
        $this->assertSame(0, DailyMeal::query()->whereDate('meal_date', '2026-12-07')->count());

        $holiday = DailyMeal::query()->whereDate('meal_date', '2026-12-08')->firstOrFail();

        $this->assertSame([
            'Congregatia A',
            'Congregatia B',
            'Congregatia C',
        ], $holiday->contributingCongregations->pluck('name')->all());
        $this->assertSame('sarbatoare legala (zi programata)', $holiday->contribution_notes);
        $this->assertNull($holiday->notes);
        $this->assertFalse($holiday->is_free_day);
        $this->assertSame($firstWeek->id, $holiday->week_id);

        $this->artisan('oms:import-program', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('Nimic de modificat; contributorii din program sunt deja la zi.')
            ->assertSuccessful();

        $this->assertSame(5, Congregation::count());
        $this->assertSame(2, Week::count());
        $this->assertSame(6, DailyMeal::count());
    }

    public function test_an_identical_duplicate_row_is_reported_and_imported_only_once(): void
    {
        $path = $this->writeCsv(<<<'CSV'
        saptamana,data,congregatia_1,congregatia_2,congregatia_3,nota
        1,2027-01-04,Congregatia A,Congregatia B,Congregatia C,
        1,2027-01-04,Congregatia A,Congregatia B,Congregatia C,
        1,2027-01-05,Congregatia A,Congregatia B,Congregatia C,
        CSV);

        $this->artisan('oms:import-program', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('Linii duplicat in CSV (ignorate)')
            ->assertSuccessful();

        $this->assertSame(2, DailyMeal::count());
        $this->assertSame(3, Congregation::count());
    }

    public function test_import_replaces_only_contributors_and_preserves_every_other_day_detail(): void
    {
        $cooks = Congregation::factory()->create(['name' => 'Bucuresti Est']);
        $oldContributor = Congregation::factory()->create(['name' => 'Contribuitor vechi']);
        $week = Week::factory()->for($cooks)->create([
            'week_number' => 2,
            'start_date' => '2026-12-07',
            'include_saturday' => true,
        ]);
        $menu = Menu::factory()->create(['name' => 'Meniu de pastrat']);
        $meal = DailyMeal::factory()->for($week)->for($cooks)->for($menu)->create([
            'meal_date' => '2026-12-07',
            'estimated_people' => 40,
            'confirmed_people' => 38,
            'maximum_budget' => 500,
            'actual_cost' => 432.10,
            'notes' => 'Observatiile bucatarului',
            'contribution_notes' => 'Nota veche',
        ]);
        $meal->syncContributingCongregations([$oldContributor->id]);
        Volunteer::factory()->for($meal)->create(['phone' => null]);

        $path = $this->writeCsv(<<<'CSV'
        saptamana,data,congregatia_1,congregatia_2,congregatia_3,nota
        2,2026-12-07,Contribuitor nou A,Contribuitor nou B,Contribuitor nou C,Nota noua
        2,2026-12-12,Contribuitor nou A,Contribuitor nou B,Contribuitor nou C,
        CSV);

        $this->artisan('oms:import-program', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('contribuitori actualizati')
            ->assertSuccessful();

        $fresh = $meal->fresh();

        $this->assertSame($cooks->id, $fresh->congregation_id);
        $this->assertSame($menu->id, $fresh->menu_id);
        $this->assertSame(40, $fresh->estimated_people);
        $this->assertSame(38, $fresh->confirmed_people);
        $this->assertSame('500.00', (string) $fresh->maximum_budget);
        $this->assertSame('432.10', (string) $fresh->actual_cost);
        $this->assertSame('Observatiile bucatarului', $fresh->notes);
        $this->assertSame('Nota noua', $fresh->contribution_notes);
        $this->assertSame(1, $fresh->volunteers()->count());
        $this->assertSame(
            ['Contribuitor nou A', 'Contribuitor nou B', 'Contribuitor nou C'],
            $fresh->contributingCongregations->pluck('name')->all(),
        );
        $this->assertFalse($fresh->hasContributingCongregation((int) $oldContributor->id));
        $this->assertFalse($fresh->hasContributingCongregation((int) $cooks->id));

        $saturday = DailyMeal::query()->whereDate('meal_date', '2026-12-12')->sole();

        $this->assertSame($cooks->id, $saturday->congregation_id);
        $this->assertSame($week->id, $saturday->week_id);
        $this->assertSame($cooks->id, $week->fresh()->congregation_id);
    }

    public function test_a_tuesday_start_csv_uses_the_iso_monday_without_a_conflict(): void
    {
        $cooks = Congregation::factory()->create(['name' => 'Bucuresti Titan']);
        $week = Week::factory()->for($cooks)->create([
            'week_number' => 3,
            'start_date' => '2026-12-15',
        ]);

        $path = $this->writeCsv(<<<'CSV'
        saptamana,data,congregatia_1,congregatia_2,congregatia_3,nota
        3,2026-12-15,Contribuitor A,Contribuitor B,Contribuitor C,
        3,2026-12-16,Contribuitor A,Contribuitor B,Contribuitor C,
        3,2026-12-17,Contribuitor A,Contribuitor B,Contribuitor C,
        3,2026-12-18,Contribuitor A,Contribuitor B,Contribuitor C,
        CSV);

        $this->artisan('oms:import-program', ['file' => $path, '--apply' => true])
            ->doesntExpectOutputToContain('Conflicte fata de baza de date')
            ->expectsOutputToContain('Program importat:')
            ->assertSuccessful();

        $this->assertSame('2026-12-14', $week->fresh()->start_date->toDateString());
        $this->assertSame($cooks->id, $week->fresh()->congregation_id);
        $this->assertSame(4, DailyMeal::count());
        $this->assertSame(0, DailyMeal::query()->whereDate('meal_date', '2026-12-14')->count());
    }

    public function test_empty_days_are_kept_without_the_explicit_flag(): void
    {
        $week = Week::factory()->create(['week_number' => 1, 'start_date' => '2026-12-07']);
        $empty = DailyMeal::factory()->for($week)->create([
            'meal_date' => '2026-12-09',
            'menu_id' => null,
            'estimated_people' => 0,
            'notes' => null,
        ]);

        $path = $this->writeCsv($this->programWithGaps());

        $this->artisan('oms:import-program', ['file' => $path, '--apply' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('daily_meals', ['id' => $empty->id]);
    }

    public function test_only_truly_empty_days_are_deleted_with_the_explicit_flag(): void
    {
        $cooks = Congregation::factory()->create();
        $week = Week::factory()->for($cooks)->create(['week_number' => 1, 'start_date' => '2026-12-07']);
        $menu = Menu::factory()->create();

        $empty = DailyMeal::factory()->for($week)->create([
            'meal_date' => '2026-12-09',
            'menu_id' => null,
            'estimated_people' => 0,
            'notes' => null,
        ]);
        $withMenu = DailyMeal::factory()->for($week)->for($menu)->create([
            'meal_date' => '2026-12-11',
            'notes' => null,
        ]);

        $path = $this->writeCsv($this->programWithGaps());

        $this->artisan('oms:import-program', [
            'file' => $path,
            '--apply' => true,
            '--elimina-zile-goale' => true,
        ])
            ->expectsOutputToContain('zile goale sterse 1')
            ->assertSuccessful();

        $this->assertDatabaseMissing('daily_meals', ['id' => $empty->id]);
        $this->assertDatabaseHas('daily_meals', ['id' => $withMenu->id]);
    }

    private function smallProgram(): string
    {
        return <<<'CSV'
        saptamana,data,congregatia_1,congregatia_2,congregatia_3,nota
        1,2026-12-08,Congregatia A,Congregatia B,Congregatia C,sarbatoare legala (zi programata)
        1,2026-12-09,Congregatia A,Congregatia D,Congregatia E,
        1,2026-12-10,Congregatia B,Congregatia D,Congregatia E,
        1,2026-12-11,Congregatia C,Congregatia D,Congregatia E,
        2,2026-12-14,Congregatia A,Congregatia B,Congregatia C,
        2,2026-12-19,Congregatia A,Congregatia D,Congregatia E,
        CSV;
    }

    private function programWithGaps(): string
    {
        return <<<'CSV'
        saptamana,data,congregatia_1,congregatia_2,congregatia_3,nota
        1,2026-12-08,Congregatia A,Congregatia B,Congregatia C,
        1,2026-12-10,Congregatia A,Congregatia B,Congregatia C,
        1,2026-12-12,Congregatia A,Congregatia B,Congregatia C,
        CSV;
    }

    private function writeCsv(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'program_');
        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
