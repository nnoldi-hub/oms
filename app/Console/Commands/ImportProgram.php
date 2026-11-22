<?php

namespace App\Console\Commands;

use App\Services\ProgramImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportProgram extends Command
{
    protected $signature = 'oms:import-program {file?} {--dry-run} {--apply} {--elimina-zile-goale}';

    protected $description = 'Importa contributorii din programul CSV, pastrand congregatia care gatesc si toate datele zilelor';

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

        $removeEmptyDays = (bool) $this->option('elimina-zile-goale');

        try {
            $plan = $importer->plan($path, $removeEmptyDays);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');

        $this->info($apply
            ? 'Mod aplicare: contributorii din CSV vor fi scrisi in baza de date; celelalte date ale zilelor raman intacte.'
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
        $this->printList('Zile goale care vor fi sterse doar cu --elimina-zile-goale', array_map(
            fn (array $day): string => $day['label'],
            $plan['emptyDaysToDelete'],
        ));
        $this->printList('Zile din baza de date absente in CSV (nu sunt sterse)', $plan['absentInDatabase']);

        if ($plan['conflicts'] !== []) {
            $this->printList('Conflicte fata de baza de date', $plan['conflicts']);
            $this->error('Nu s-a modificat nimic din cauza conflictelor.');

            return self::FAILURE;
        }

        $changeCount = count($plan['congregationsToCreate'])
            + count(array_filter($plan['weeks'], fn (array $week): bool => $week['action'] !== 'ok'))
            + count($plan['days'])
            + count($plan['emptyDaysToDelete']);

        if ($changeCount === 0) {
            $this->info('Nimic de modificat; contributorii din program sunt deja la zi.');

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
            'Program importat: congregatii create %d, saptamani actualizate %d, zile create %d, contributori sincronizati %d, note actualizate %d, zile goale sterse %d.',
            $counts['congregations'],
            $counts['weeks'],
            $counts['days'],
            $counts['contributors'],
            $counts['notes'],
            $counts['empty_days'],
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
            'update' => 'contribuitori actualizati',
            'update_note' => 'nota actualizata',
            default => 'neschimbat',
        };
    }
}
