<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Week;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleGenerator
{
    /**
     * @param  array<int, int>  $congregationIds
     */
    public function generate(string $startDate, int $weeksCount, array $congregationIds): void
    {
        $congregations = Congregation::query()->whereIn('id', $congregationIds)->get()->keyBy('id');

        if (count($congregationIds) !== 3 || $congregations->count() !== 3) {
            throw ValidationException::withMessages(['congregation_ids' => 'Selecteaza exact trei congregatii, in ordinea dorita pentru rotatie.']);
        }

        $start = CarbonImmutable::parse($startDate)->startOfDay();

        if (! $start->isMonday()) {
            throw ValidationException::withMessages(['start_date' => 'O saptamana de masa trebuie sa inceapa intr-o zi de luni. Alege o data de luni.']);
        }

        $end = $start->addWeeks($weeksCount - 1)->addDays(4);

        $rotation = collect($congregationIds)->map(fn (int $id): Congregation => $congregations->get($id));

        DB::transaction(function () use ($start, $end, $weeksCount, $rotation): void {
            if (DailyMeal::query()->whereBetween('meal_date', [$start->toDateString(), $end->toDateString()])->exists()) {
                throw ValidationException::withMessages(['start_date' => 'Intervalul ales contine deja zile de masa. Alege o data de inceput fara suprapuneri.']);
            }

            $firstWeekNumber = (int) Week::query()->max('week_number') + 1;

            foreach (range(0, $weeksCount - 1) as $weekIndex) {
                $weekStart = $start->addWeeks($weekIndex);
                $primaryCongregation = $rotation[$weekIndex % $rotation->count()];
                $week = Week::create([
                    'week_number' => $firstWeekNumber + $weekIndex,
                    'start_date' => $weekStart->toDateString(),
                    'congregation_id' => $primaryCongregation->id,
                ]);

                foreach (range(0, 4) as $dayIndex) {
                    $congregation = $weekIndex === $weeksCount - 1
                        ? $rotation[intdiv($dayIndex, 2)]
                        : $primaryCongregation;

                    DailyMeal::create([
                        'week_id' => $week->id,
                        'meal_date' => $weekStart->addDays($dayIndex)->toDateString(),
                        'congregation_id' => $congregation->id,
                        'estimated_people' => 0,
                        'status' => 'draft',
                    ]);
                }
            }
        });
    }
}