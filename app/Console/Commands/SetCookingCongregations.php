<?php

namespace App\Console\Commands;

use App\Models\Congregation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetCookingCongregations extends Command
{
    protected $signature = 'oms:set-cooking-congregations
                            {names* : Numele congregatiilor care gatesc (exact cum apar in baza de date)}
                            {--apply : Scrie modificarile; implicit este doar simulare}';

    protected $description = 'Marcheaza congregatiile care gatesc; toate celelalte raman contribuitoare';

    public function handle(): int
    {
        $requested = array_values(array_unique(array_filter(
            array_map(fn (string $name): string => trim($name), (array) $this->argument('names')),
        )));

        if ($requested === []) {
            $this->error('Trebuie sa dai cel putin un nume de congregatie.');

            return self::FAILURE;
        }

        $congregations = Congregation::query()->orderBy('name')->get();
        $byName = $congregations->mapWithKeys(fn (Congregation $c): array => [mb_strtolower(trim($c->name)) => $c]);

        $missing = [];
        $cooking = [];

        foreach ($requested as $name) {
            $congregation = $byName->get(mb_strtolower($name));

            if ($congregation === null) {
                $missing[] = $name;

                continue;
            }

            $cooking[$congregation->id] = $congregation;
        }

        if ($missing !== []) {
            $this->error('Congregatii gasite in lista, dar absente din baza de date:');

            foreach ($missing as $name) {
                $this->line(' - '.$name);
            }

            return self::FAILURE;
        }

        $toPromote = $congregations->filter(
            fn (Congregation $c): bool => isset($cooking[$c->id]) && $c->type !== 'gateste',
        );
        $toDemote = $congregations->filter(
            fn (Congregation $c): bool => ! isset($cooking[$c->id]) && $c->type === 'gateste',
        );

        $this->info('Congregatii care gatesc (conform listei): '.implode(', ', array_map(
            fn (Congregation $c): string => $c->name,
            array_values($cooking),
        )));

        $this->table(
            ['Actiune', 'Congregatie'],
            $toPromote->map(fn (Congregation $c): array => ['devine gateste', $c->name])
                ->concat($toDemote->map(fn (Congregation $c): array => ['devine contribuie', $c->name]))
                ->values()
                ->all(),
        );

        $this->info('Nu se modifica weeks.congregation_id si nici daily_meals.congregation_id.');

        if (! $this->option('apply')) {
            $this->newLine();
            $this->info('Mod de testare (implicit): nu s-a modificat nimic. Foloseste --apply pentru a scrie.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($toPromote, $toDemote): void {
            foreach ($toPromote as $congregation) {
                $congregation->update(['type' => 'gateste']);
            }

            foreach ($toDemote as $congregation) {
                $congregation->update(['type' => 'contribuie']);
            }
        });

        $this->info(sprintf(
            'Actualizat: %d congregatii devin gateste, %d devin contribuie.',
            $toPromote->count(),
            $toDemote->count(),
        ));

        return self::SUCCESS;
    }
}
