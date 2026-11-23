<?php

namespace App\Services;

use App\Models\Week;
use App\Support\CongregationAccess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class WeekScheduleSummaryService
{
    /**
     * Rezumatul intregului program este pentru administrator si pentru
     * coordonatorii celor trei congregatii care gatesc (Est, Titan, Balta Alba).
     * Coordonatorii doar contribuitori nu il vad.
     */
    public function canView(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isCoordinator() && CongregationAccess::cooksAnywhere($user);
    }

    /**
     * @return Collection<int, array{week_id: int, week_number: int, period: string, working_days: string, cooking: string, days_count: int}>
     */
    public function summaries(): Collection
    {
        return Week::query()
            ->with([
                'cookingCongregation',
                'dailyMeals' => fn ($query) => $query
                    ->where('is_free_day', false)
                    ->with('cookingCongregation')
                    ->orderBy('meal_date'),
            ])
            ->orderBy('week_number')
            ->get()
            ->map(fn (Week $week): array => $this->summarize($week))
            ->values();
    }

    /** @return array{week_id: int, week_number: int, period: string, working_days: string, cooking: string, days_count: int} */
    private function summarize(Week $week): array
    {
        $dates = $week->dailyMeals
            ->filter(fn ($meal): bool => $meal->meal_date !== null)
            ->map(fn ($meal): string => $meal->meal_date->toDateString())
            ->unique()
            ->sort()
            ->values();

        $start = $dates->isNotEmpty()
            ? CarbonImmutable::parse($dates->first())
            : $week->start_date;
        $end = $dates->isNotEmpty()
            ? CarbonImmutable::parse($dates->last())
            : $start;

        $cookingNames = $week->dailyMeals
            ->pluck('cookingCongregation.name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($cookingNames === [] && $week->cookingCongregation !== null) {
            $cookingNames = [$week->cookingCongregation->name];
        }

        return [
            'week_id' => (int) $week->id,
            'week_number' => (int) $week->week_number,
            'period' => $start->format('d.m').' – '.$end->format('d.m.Y'),
            'working_days' => $this->workingDaysLabel($dates),
            'cooking' => $cookingNames === [] ? 'Nealocata' : implode(', ', $cookingNames),
            'days_count' => $dates->count(),
        ];
    }

    /** @param Collection<int, string> $dates */
    private function workingDaysLabel(Collection $dates): string
    {
        if ($dates->isEmpty()) {
            return 'Fără zile programate';
        }

        $runs = [];
        $current = [$dates->first()];

        foreach ($dates->skip(1) as $date) {
            $previous = CarbonImmutable::parse($current[count($current) - 1]);

            if ($previous->addDay()->toDateString() === $date) {
                $current[] = $date;

                continue;
            }

            $runs[] = $current;
            $current = [$date];
        }

        $runs[] = $current;

        return collect($runs)
            ->map(function (array $run): string {
                $first = CarbonImmutable::parse($run[0])->locale('ro')->translatedFormat('l');

                if (count($run) === 1) {
                    return $first;
                }

                $last = CarbonImmutable::parse($run[count($run) - 1])->locale('ro')->translatedFormat('l');

                return $first.'–'.$last;
            })
            ->implode(', ');
    }
}
