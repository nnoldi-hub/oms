<?php

namespace App\Filament\Widgets;

use App\Models\DailyMeal;
use App\Services\DailyMealCostCalculator;
use App\Services\DonationSummaryService;
use App\Support\CongregationAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DonationStatsOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        $user = auth()->user();

        return ($user?->canManageContributions() ?? false) || ($user?->isProjectSupervisor() ?? false);
    }

    protected function getStats(): array
    {
        $summary = app(DonationSummaryService::class);
        $donations = $summary->totals();

        $meals = DailyMeal::query()
            ->when(
                auth()->user()?->isScopedToCongregation(),
                fn ($query) => CongregationAccess::scopeMeals($query, auth()->user()),
            )
            ->get();

        $estimatedCost = (float) $meals->sum(
            fn (DailyMeal $meal): float => app(DailyMealCostCalculator::class)->calculate($meal)['total_cost'],
        );

        $coverage = $summary->coverage($estimatedCost);

        return [
            Stat::make('Donații primite', number_format($donations['received'], 2, '.', ' ').' RON')
                ->description('Valoarea donațiilor confirmate')
                ->color('success'),
            Stat::make('Donații promise', number_format($donations['promised'], 2, '.', ' ').' RON')
                ->description('Valoarea donațiilor anunțate')
                ->color('warning'),
            Stat::make('Acoperire cost estimat', number_format($coverage['coverage_percent'], 1, '.', ' ').'%')
                ->description('Cost rămas de acoperit: '.number_format($coverage['remaining'], 2, '.', ' ').' RON')
                ->color($coverage['coverage_percent'] >= 100 ? 'success' : 'primary'),
        ];
    }
}
