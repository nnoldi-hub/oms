<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
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

        $this->assertSame('2026-12-08', $firstWeek->start_date->toDateString());
        $this->assertFalse($firstWeek->include_saturday);
        $this->assertNull($firstWeek->congregation_id);
        $this->assertTrue($secondWeek->include_saturday);
        $this->assertSame(0, DailyMeal::query()->whereDate('meal_date', '2026-12-07')->count());

        $holiday = DailyMeal::query()->whereDate('meal_date', '2026-12-08')->firstOrFail();

        $this->assertSame([
            'Congregatia A',
            'Congregatia B',
            'Congregatia C',
        ], $holiday->congregations->pluck('name')->all());
        $this->assertSame('sarbatoare legala (zi programata)', $holiday->notes);
        $this->assertFalse($holiday->is_free_day);
        $this->assertSame($firstWeek->id, $holiday->week_id);

        $this->artisan('oms:import-program', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('Nimic de modificat; programul este deja la zi.')
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

    public function test_conflicts_stop_writes_and_days_absent_from_csv_are_reported(): void
    {
        $existing = Congregation::factory()->create(['name' => 'Congregatia Veche']);
        $week = Week::factory()->create(['congregation_id' => null]);
        DailyMeal::factory()->for($week)->for($existing)->create(['meal_date' => '2026-12-08']);
        DailyMeal::factory()->for($week)->for($existing)->create(['meal_date' => '2026-12-09']);

        $path = $this->writeCsv(<<<'CSV'
        saptamana,data,congregatia_1,congregatia_2,congregatia_3,nota
        1,2026-12-08,Congregatia A,Congregatia B,Congregatia C,
        1,2026-12-10,Congregatia A,Congregatia B,Congregatia C,
        CSV);

        $this->artisan('oms:import-program', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('Conflicte fata de baza de date')
            ->expectsOutputToContain('Zile din baza de date absente in CSV (nu sunt sterse)')
            ->expectsOutputToContain('Nu s-a modificat nimic din cauza conflictelor.')
            ->assertFailed();

        $this->assertSame(1, Congregation::count());
        $this->assertSame(1, Week::count());
        $this->assertSame(2, DailyMeal::count());
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

    private function writeCsv(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'program_');
        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
