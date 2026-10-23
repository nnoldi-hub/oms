<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\DailyMealCostSnapshot;
use App\Models\Week;

/**
 * Ingheata costul zilelor incheiate, ca schimbarile ulterioare de pret sau de reteta
 * sa nu modifice rapoartele vechi.
 *
 * O zi este incheiata cand data ei a trecut sau cand e bifata "gatit".
 *
 * Metoda snapshotAllClosedMeals() trebuie apelata INAINTE de orice schimbare care
 * poate modifica costurile (pret de ingredient, reteta, import) si noaptea, din cron.
 */
class DailyMealCostSnapshotService
{
    public function __construct(private DailyMealCostCalculator $costCalculator) {}

    /**
     * Salveaza costul tuturor zilelor incheiate care nu au inca snapshot.
     * Returneaza cate snapshot-uri noi s-au creat.
     */
    public function snapshotAllClosedMeals(): int
    {
        $created = 0;

        DailyMeal::query()
            ->where('is_free_day', false)
            ->where(function ($query): void {
                $query->whereDate('meal_date', '<', today()->toDateString())
                    ->orWhere('is_cooked', true);
            })
            ->doesntHave('costSnapshot')
            ->with(['menu', 'soupMenu', 'dessertMenu'])
            ->chunkById(50, function ($meals) use (&$created): void {
                foreach ($meals as $meal) {
                    if ($this->snapshotMeal($meal)) {
                        $created++;
                    }
                }
            });

        return $created;
    }

    /**
     * Pastrat pentru WeeklyPlanReportBuilder: ingheata zilele incheiate ale saptamanii.
     */
    public function snapshotWeekIfClosed(Week $week): int
    {
        $week->loadMissing([
            'dailyMeals.menu',
            'dailyMeals.soupMenu',
            'dailyMeals.dessertMenu',
        ]);

        $created = 0;

        foreach ($week->dailyMeals as $dailyMeal) {
            if ($this->isMealClosed($dailyMeal) && $this->snapshotMeal($dailyMeal)) {
                $created++;
            }
        }

        return $created;
    }

    public function snapshotMealIfClosed(DailyMeal $dailyMeal): bool
    {
        return $this->isMealClosed($dailyMeal) && $this->snapshotMeal($dailyMeal);
    }

    public function isMealClosed(DailyMeal $dailyMeal): bool
    {
        if ($dailyMeal->is_free_day) {
            return false;
        }

        return (bool) $dailyMeal->is_cooked
            || ($dailyMeal->meal_date !== null && $dailyMeal->meal_date->lt(today()));
    }

    public function isWeekClosed(Week $week): bool
    {
        $week->loadMissing('dailyMeals');

        if ($week->dailyMeals->isEmpty()) {
            return false;
        }

        return $week->dailyMeals->every(
            fn (DailyMeal $dailyMeal): bool => (bool) $dailyMeal->is_cooked
                || ($dailyMeal->meal_date !== null && $dailyMeal->meal_date->lt(today())),
        );
    }

    /**
     * Sterge snapshot-ul unei zile (de exemplu dupa ce s-au corectat portiile sau costul real).
     * Va fi refacut la urmatorul apel, cu datele zilei de atunci.
     */
    public function forgetMeal(DailyMeal $dailyMeal): void
    {
        DailyMealCostSnapshot::query()->where('daily_meal_id', $dailyMeal->id)->delete();
        $dailyMeal->unsetRelation('costSnapshot');
    }

    private function snapshotMeal(DailyMeal $dailyMeal): bool
    {
        if (DailyMealCostSnapshot::query()->where('daily_meal_id', $dailyMeal->id)->exists()) {
            return false;
        }

        $fresh = $this->costCalculator->calculateFresh($dailyMeal);
        $portions = $dailyMeal->portions();

        // createOrFirst foloseste cheia unica: daca doua cereri ajung simultan,
        // a doua primeste snapshot-ul existent in loc de eroare.
        $snapshot = DailyMealCostSnapshot::query()->createOrFirst(
            ['daily_meal_id' => $dailyMeal->id],
            [
                'congregation_id' => $dailyMeal->congregation_id,
                'meal_date' => $dailyMeal->meal_date,
                'portions' => $portions,
                'ingredients_cost' => $fresh['breakdown']['ingredients_cost'],
                'fixed_costs_cost' => $fresh['breakdown']['fixed_costs_cost'],
                'packaging_cost' => $fresh['breakdown']['packaging_cost'],
                'total_cost' => $fresh['total_cost'],
                'cost_per_portion' => $portions > 0 && ! $fresh['has_missing_prices']
                    ? round($fresh['total_cost'] / $portions, 2)
                    : null,
                'has_missing_prices' => $fresh['has_missing_prices'],
            ],
        );

        $dailyMeal->setRelation('costSnapshot', $snapshot);

        return $snapshot->wasRecentlyCreated;
    }
}