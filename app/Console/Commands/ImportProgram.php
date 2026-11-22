<?php

namespace App\Console\Commands;

use App\Services\ProgramImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportProgram extends Command
{
    protected $signature = 'oms:import-program {file?} {--dry-run} {--apply}';

    protected $description = 'Importa programul celor trei congregatii pe zi dintr-un fisier CSV';

    public function handle(ProgramImportService $importer): int
    {
        if ($this->option('dry-run') && $this->option('apply')) {
            $this->error('Foloseste --dry-run sau --apply, nu ambele optiuni.');

            return self::FAILURE;
        }

        $path = $this->argument('file') ?: database_path('data/program-congregatii.csv');

        if (! is_file($path)) {
            $this->error("Fisierul nu exista: {$path}");

            return self::FAILURE;
        }

        try {
            $plan = $importer->plan($path);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');

        $this->info($apply
            ? 'Mod aplicare: programul din CSV va fi scris in baza de date.'
            : 'Mod de testare (implicit): nu s-a modificat nimic. Foloseste --apply pentru a scrie.');

        if ($plan['errors'] !== []) {
            $this->newLine();
            $this->error('Fisierul contine erori:');

            foreach ($plan['errors'] as $error) {
                $this->line(' - '.$error);
            }

            return self::FAILURE;
        }

        if ($plan['duplicates'] !== []) {
            $this->printList('Linii duplicat in CSV (ignorate)', $plan['duplicates']);
        }

        $this->printRows('Congregatii de creat', ['Denumire'], array_map(
            fn (string $name): array => [$name],
            $plan['congregationsToCreate'],
        ));

        $this->printRows('Saptamani', ['Actiune', 'Detalii'], array_map(
            fn (array $week): array => [$this->actionLabel($week['action']), $week['label']],
            $plan['weeks'],
        ));

        $this->printRows('Zile de masa', ['Actiune', 'Detalii'], array_map(
            fn (array $day): array => [$this->actionLabel($day['action']), $day['label']],
            $plan['days'],
        ));

        $this->printList('Zile din CSV absente in baza de date', $plan['missingInDatabase']);
        $this->printList('Zile din baza de date absente in CSV (nu sunt sterse)', $plan['absentInDatabase']);

        if ($plan['conflicts'] !== []) {
            $this->printList('Conflicte fata de baza de date', $plan['conflicts']);
            $this->error('Nu s-a modificat nimic din cauza conflictelor.');

            return self::FAILURE;
        }

        $changeCount = count($plan['congregationsToCreate'])
            + count(array_filter($plan['weeks'], fn (array $week): bool => $week['action'] !== 'ok'))
            + count(array_filter($plan['days'], fn (array $day): bool => $day['action'] !== 'ok'));

        if ($changeCount === 0) {
            $this->info('Nimic de modificat; programul este deja la zi.');

            return self::SUCCESS;
        }

        if (! $apply) {
            $this->newLine();
            $this->info('Mod de testare (--dry-run): nu s-a modificat nimic.');

            return self::SUCCESS;
        }

        $counts = $importer->apply($plan);

        $this->newLine();
        $this->info(sprintf(
            'Program importat: congregatii create %d, saptamani create/actualizate %d, zile create %d, note actualizate %d.',
            $counts['congregations'],
            $counts['weeks'],
            $counts['days'],
            $counts['notes'],
        ));

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $rows
     */
    private function printList(string $title, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $this->newLine();
        $this->info($title);

        foreach ($rows as $row) {
            $this->line(' - '.$row);
        }
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int, string>>  $rows
     */
    private function printRows(string $title, array $headers, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $this->newLine();
        $this->info($title);
        $this->table($headers, $rows);
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'create' => 'creat',
            'update' => 'actualizat',
            'update_note' => 'nota actualizata',
            default => 'neschimbat',
        };
    }
}
