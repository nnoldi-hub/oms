<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShiftWeekdays extends Command
{
    protected $signature = 'oms:shift-weekdays {--dry-run : Afiseaza ce s-ar schimba, fara a salva} {--force : Nu cere confirmarea finala}';

    protected $description = 'Muta programul astfel incat fiecare saptamana sa inceapa lunea (Luni-Vineri)';

    /** Zile legale nelucratoare care pot cadea in program. */
    private const HOLIDAYS = [
        '01-01' => 'Anul Nou',
        '01-02' => 'Anul Nou',
        '01-24' => 'Ziua Unirii',
        '05-01' => 'Ziua Muncii',
        '06-01' => 'Ziua Copilului',
        '08-15' => 'Adormirea Maicii Domnului',
        '11-30' => 'Sfantul Andrei',
        '12-01' => 'Ziua Nationala',
        '12-25' => 'Craciun',
        '12-26' => 'Craciun',
    ];

    public function handle(): int
    {
        $starts = DB::table('weeks')->orderBy('week_number')->pluck('start_date');

        if ($starts->isEmpty()) {
            $this->info('Nu exista saptamani in program.');

            return self::SUCCESS;
        }

        $delta = $this->daysUntilMonday(CarbonImmutable::parse($starts->first()));

        if ($delta === 0) {
            $this->info('Programul incepe deja intr-o zi de luni; nu este necesara nicio mutare.');

            return self::SUCCESS;
        }

        $differentStarts = $starts->filter(
            fn (string $start) => $this->daysUntilMonday(CarbonImmutable::parse($start)) !== $delta,
        );

        if ($differentStarts->isNotEmpty()) {
            $this->error('Saptamanile nu au aceeasi zi de start, asa ca mutarea nu se poate aplica uniform. Se opreste.');

            return self::FAILURE;
        }

        $firstStart = CarbonImmutable::parse($starts->first());
        $lastEnd = CarbonImmutable::parse($starts->last())->addDays(4);
        $range = [$firstStart->toDateString(), $lastEnd->toDateString()];

        $this->info(sprintf(
            'Saptamana 1: %s -> %s; ultima zi: %s -> %s (mutare inainte cu %d zile).',
            $firstStart->toDateString(),
            $firstStart->addDays($delta)->toDateString(),
            $lastEnd->toDateString(),
            $lastEnd->addDays($delta)->toDateString(),
            $delta,
        ));

        $weeks = DB::table('weeks')->orderBy('week_number')->get(['id', 'start_date']);
        $meals = DB::table('daily_meals')->orderByDesc('meal_date')->get(['id', 'meal_date', 'is_free_day']);
        $plans = DB::table('daily_supply_plans')->whereBetween('plan_date', $range)
            ->orderByDesc('plan_date')->get(['id', 'plan_date']);
        $contributions = DB::table('supply_contributions')->whereBetween('delivery_date', $range)
            ->orderByDesc('delivery_date')->get(['id', 'delivery_date']);

        $this->table(
            ['Tabel', 'Randuri mutate'],
            [
                ['weeks (start_date)', $weeks->count()],
                ['daily_meals (meal_date)', $meals->count()],
                ['daily_supply_plans (plan_date)', $plans->count()],
                ['supply_contributions (delivery_date)', $contributions->count()],
            ],
        );

        foreach ($this->holidayWarnings($meals, $delta) as $warning) {
            $this->warn($warning);
        }

        if ($this->option('dry-run')) {
            $this->info('Mod de testare (--dry-run): nu s-a modificat nimic.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(sprintf('Muti intreg programul inainte cu %d zile?', $delta))) {
            $this->info('Mutarea a fost anulata.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($delta, $weeks, $meals, $plans, $contributions): void {
            foreach ($weeks as $week) {
                DB::table('weeks')->where('id', $week->id)->update([
                    'start_date' => CarbonImmutable::parse($week->start_date)->addDays($delta)->toDateString(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($meals as $meal) {
                DB::table('daily_meals')->where('id', $meal->id)->update([
                    'meal_date' => CarbonImmutable::parse($meal->meal_date)->addDays($delta)->toDateString(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($plans as $plan) {
                DB::table('daily_supply_plans')->where('id', $plan->id)->update([
                    'plan_date' => CarbonImmutable::parse($plan->plan_date)->addDays($delta)->toDateString(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($contributions as $contribution) {
                DB::table('supply_contributions')->where('id', $contribution->id)->update([
                    'delivery_date' => CarbonImmutable::parse($contribution->delivery_date)->addDays($delta)->toDateString(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->info('Programul a fost mutat pe Luni-Vineri.');

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $meals
     * @return array<int, string>
     */
    private function holidayWarnings($meals, int $delta): array
    {
        $warnings = [];

        foreach ($meals as $meal) {
            $newDate = CarbonImmutable::parse($meal->meal_date)->addDays($delta);

            if (! $meal->is_free_day && isset(self::HOLIDAYS[$newDate->format('m-d')])) {
                $warnings[] = sprintf(
                    'Ziua %s cade de %s (sarbatoare legala). Verifica daca se lucreaza.',
                    $newDate->toDateString(),
                    self::HOLIDAYS[$newDate->format('m-d')],
                );
            }
        }

        return $warnings;
    }

    private function daysUntilMonday(CarbonImmutable $date): int
    {
        return (1 - $date->isoWeekday() + 7) % 7;
    }
}
