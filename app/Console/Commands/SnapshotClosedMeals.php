<?php

namespace App\Console\Commands;

use App\Services\DailyMealCostSnapshotService;
use Illuminate\Console\Command;

class SnapshotClosedMeals extends Command
{
    protected $signature = 'oms:snapshot-closed-meals';

    protected $description = 'Salveaza costul zilelor trecute sau gatite, ca schimbarile de pret sa nu modifice rapoartele vechi.';

    public function handle(DailyMealCostSnapshotService $service): int
    {
        $created = $service->snapshotAllClosedMeals();

        $this->info("Snapshot-uri create: {$created}");

        return self::SUCCESS;
    }
}