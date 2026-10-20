<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Week;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleGenerator
{
    public const MEALS_PER_WEEK = 5;

    /**
     * @param  array<int, int>  $congregationIds
     */
    public function generate(string $startDate, int $weeksCount, array $congregationIds, bool $includeSaturday = false): void
    {
        $congregations = Congregation::query()->whereIn('id', $congregationIds)->get()->keyBy('id');

        if (count($congregationIds) !== 3 || $congregations->count() !== 3) {
            throw ValidationException::withMessages(['congregation_ids' => 'Selecteaza exact trei congregatii, in ordinea dorita pentru rotatie.']);
        }

        $start = CarbonImmutable::parse($startDate)->startOfDay();

        if (! $includeSaturday && $start->isWeekend()) {
            throw ValidationException::withMessages(['start_date' => 'Data de inceput cade intr-un weekend. Alege o zi lucratoare sau activeaza sambata ca zi de lucru.']);
        }

        $workingDays = $includeSaturday ? [1, 2, 3, 4, 5, 6] : [1, 2, 3, 4, 5];
        $mealDates = $this->mealDates($start, $weeksCount, $workingDays);
        $end = $mealDates[count($mealDates) - 1];

        $rotation = collect($congregationIds)->map(fn (int $id): Congregation => $congregations->get($id));

        DB::transaction(function () use ($mealDates, $start, $end, $weeksCount, $includeSaturday, $rotation): void {
            if (DailyMeal::query()->whereBetween('meal_date', [$start->toDateString(), $end->toDateString()])->exists()) {
                throw ValidationException::withMessages(['start_date' => 'Intervalul ales contine deja zile de masa. Alege o data de inceput fara suprapuneri.']);
            }

            $firstWeekNumber = (int) Week::query()->max('week_number') + 1;

            foreach (range(0, $weeksCount - 1) as $weekIndex) {
                $weekStart = $mealDates[$weekIndex * self::MEALS_PER_WEEK];
                $primaryCongregation = $rotation[$weekIndex % $rotation->count()];
                $week = Week::create([
                    'week_number' => $firstWeekNumber + $weekIndex,
                    'start_date' => $weekStart->toDateString(),
                    'include_saturday' => $includeSaturday,
                    'congregation_id' => $primaryCongregation->id,
                ]);

                foreach (range(0, self::MEALS_PER_WEEK - 1) as $dayIndex) {
                    $congregation = $weekIndex === $weeksCount - 1
                        ? $rotation[intdiv($dayIndex, 2)]
                        : $primaryCongregation;

                    DailyMeal::create([
                        'week_id' => $week->id,
                        'meal_date' => $mealDates[$weekIndex * self::MEALS_PER_WEEK + $dayIndex]->toDateString(),
                        'congregation_id' => $congregation->id,
                        'estimated_people' => 0,
                        'status' => 'draft',
                    ]);
                }
            }
        });
    }

    /**
     * @param  array<int, int>  $workingDays
     * @return array<int, CarbonImmutable>
     */
    private function mealDates(CarbonImmutable $start, int $weeksCount, array $workingDays): array
    {
        $dates = [];
        $cursor = $start;
        $needed = $weeksCount * self::MEALS_PER_WEEK;

        while (count($dates) < $needed) {
            if (in_array((int) $cursor->isoWeekday(), $workingDays, true)) {
                $dates[] = $cursor;
            }

            $cursor = $cursor->addDay();
        }

        return $dates;
    }
}
