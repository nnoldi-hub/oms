<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Menu;
use Illuminate\Support\Collection;

/**
 * Rotatia retetelor depinde de ORDINEA meselor din program, nu de zilele calendaristice.
 *
 * - Felul principal: catalog_principale[ordine % N], unde ordinea numara DOAR mesele care
 *   urmeaza a fi completate automat (fara meniu inca), sortate dupa data. Zilele libere si
 *   mesele cu meniu setat manual NU consuma un loc in rotatie, deci o interventie manuala
 *   nu decaleaza restul programului.
 * - Ciorba: se pune pe a 2-a si a 4-a masa din fiecare saptamana (dupa pozitia reala in
 *   saptamana). Slujba k = 2 * (saptamana - 1) + (0 pentru a 2-a masa, 1 pentru a 4-a masa),
 *   ciorba = catalog_ciorbe[k % M]. Cele doua ciorbe dintr-o saptamana difera.
 */
class MealRotation
{
    public const MEALS_PER_WEEK = 5;

    public const SOUP_SLOTS_PER_WEEK = [1, 3];

    /**
     * Ordinea globala (index de la 0) doar pentru mesele completate automat, sarind zilele
     * libere si mesele care au deja un meniu setat.
     *
     * @param  Collection<int, DailyMeal>  $meals
     * @return array<int, int>  meal_id => index
     */
    public function mainOrder(Collection $meals): array
    {
        return $meals
            ->reject(fn (DailyMeal $meal): bool => (bool) $meal->is_free_day)
            ->filter(fn (DailyMeal $meal): bool => $meal->menu_id === null)
            ->sortBy(fn (DailyMeal $meal) => $meal->meal_date->toDateString())
            ->values()
            ->mapWithKeys(fn (DailyMeal $meal, int $index): array => [$meal->id => $index])
            ->all();
    }

    /**
     * Pozitia reala (0..4) a unei mese in saptamana ei, dupa data, excluzand zilele libere.
     *
     * @param  Collection<int, DailyMeal>  $meals  toate mesele din aceeasi saptamana
     */
    public function positionInWeek(Collection $meals, DailyMeal $target): ?int
    {
        $position = $meals
            ->where('week_id', $target->week_id)
            ->reject(fn (DailyMeal $meal): bool => (bool) $meal->is_free_day)
            ->sortBy(fn (DailyMeal $meal) => $meal->meal_date->toDateString())
            ->values()
            ->search(fn (DailyMeal $meal): bool => $meal->id === $target->id);

        return $position === false ? null : $position;
    }

    /** @param Collection<int, Menu> $catalog */
    public function mainRecipe(Collection $catalog, int $order): ?Menu
    {
        if ($catalog->isEmpty()) {
            return null;
        }

        return $catalog[$order % $catalog->count()];
    }

    /**
     * Indexul ciorbii din catalogul de ciorbe pentru o masa aflata in saptamana `weekNumber`
     * pe pozitia `dayIndex` (0..4). Returneaza null daca masa nu gazduieste ciorba.
     *
     * @param Collection<int, Menu> $catalog
     */
    public function soupRecipeIndex(int $catalogSize, int $weekNumber, int $dayIndex): ?int
    {
        if ($catalogSize === 0) {
            return null;
        }

        $slot = array_search($dayIndex, self::SOUP_SLOTS_PER_WEEK, true);

        if ($slot === false) {
            return null;
        }

        return (2 * ($weekNumber - 1) + $slot) % $catalogSize;
    }

    /** Este aceasta pozitie (a 2-a / a 4-a masa) una care gazduieste ciorba? */
    public function hostsSoup(int $dayIndex): bool
    {
        return in_array($dayIndex, self::SOUP_SLOTS_PER_WEEK, true);
    }
}
