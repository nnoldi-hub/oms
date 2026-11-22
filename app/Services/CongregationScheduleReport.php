<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\Week;
use Carbon\CarbonImmutable;

/**
 * Construieste o "saptamana virtuala" cu toate zilele in care participa o
 * congregatie, pe intreg intervalul programului.
 */
class CongregationScheduleReport
{
    public function build(Congregation $congregation): Week
    {
        $meals = $congregation->scheduledDailyMeals()
            ->with(['menu', 'soupMenu', 'dessertMenu', 'congregations', 'costSnapshot'])
            ->get();

        $week = new Week([
            'week_number' => 0,
            'start_date' => $meals->first()?->meal_date ?? CarbonImmutable::now()->toDateString(),
            'congregation_id' => $congregation->id,
        ]);
        $week->setRelation('dailyMeals', $meals->values());

        return $week;
    }
}
