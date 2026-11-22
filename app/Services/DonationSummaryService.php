<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Donation;
use App\Models\Week;
use Illuminate\Database\Eloquent\Builder;

/**
 * Agregari de donatii folosite de lista de cumparaturi, costuri si dashboard.
 * Nu afiseaza si nu returneaza date personale ale donatorilor.
 */
class DonationSummaryService
{
    /**
     * @return array{received: float, promised: float, total: float, items: int}
     */
    public function totals(?Builder $query = null): array
    {
        $donations = ($query ?? Donation::query()->visibleTo(auth()->user()))
            ->get(['status', 'amount_ron']);

        $received = (float) $donations->whereIn('status', [Donation::STATUS_RECEIVED, Donation::STATUS_USED])->sum('amount_ron');
        $promised = (float) $donations->where('status', Donation::STATUS_PROMISED)->sum('amount_ron');

        return [
            'received' => round($received, 2),
            'promised' => round($promised, 2),
            'total' => round($received + $promised, 2),
            'items' => $donations->count(),
        ];
    }

    /**
     * @return array{received: float, promised: float, total: float, items: int, estimated_cost: float, net_cost: float, remaining: float, coverage_percent: float}
     */
    public function coverage(float $estimatedCost, ?Builder $query = null): array
    {
        $totals = $this->totals($query);
        $cost = round(max(0.0, $estimatedCost), 2);
        $received = $totals['received'];

        return [
            ...$totals,
            'estimated_cost' => $cost,
            'net_cost' => round(max(0.0, $cost - $received), 2),
            'remaining' => round(max(0.0, $cost - $totals['total']), 2),
            'coverage_percent' => $cost > 0 ? round(min(100.0, $received / $cost * 100), 1) : 0.0,
        ];
    }

    public function forDailyMeal(DailyMeal $meal): Builder
    {
        $date = $meal->meal_date->toDateString();

        return Donation::query()
            ->visibleTo(auth()->user())
            ->where(function (Builder $query) use ($meal, $date): void {
                $query->where('daily_meal_id', $meal->id)
                    ->orWhereDate('date', $date)
                    ->orWhereDate('donation_date', $date);
            });
    }

    public function forWeek(Week $week): Builder
    {
        $meals = $week->relationLoaded('dailyMeals') ? $week->dailyMeals : null;
        $dates = $meals?->map(fn (DailyMeal $meal): string => $meal->meal_date->toDateString())->filter()->values();

        $start = $dates?->min() ?? $week->start_date->toDateString();
        $end = $dates?->max() ?? $week->start_date->copy()->addDays(6)->toDateString();

        return Donation::query()
            ->visibleTo(auth()->user())
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end);
    }
}
