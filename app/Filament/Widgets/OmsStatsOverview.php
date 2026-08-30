<?php

namespace App\Filament\Widgets;

use App\Models\DailyMeal;
use App\Models\Menu;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OmsStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = today();
        $todayMeal = DailyMeal::query()->whereDate('meal_date', $today)->first();
        $startOfWeek = $today->copy()->startOfWeek();
        $endOfWeek = $today->copy()->endOfWeek();
        $plannedDays = DailyMeal::query()
            ->whereBetween('meal_date', [$startOfWeek, $endOfWeek])
            ->count();

        return [
            Stat::make('Persoane estimate azi', (string) ($todayMeal?->estimated_people ?? 0))
                ->description($todayMeal === null ? 'Nu exista masa programata azi' : 'Meniu: ' . ($todayMeal->menu?->name ?? 'neales'))
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->color('primary')
                ->icon(Heroicon::OutlinedUsers),
            Stat::make('Zile planificate saptamana aceasta', (string) $plannedDays)
                ->description('Zile de masa programate in calendar')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('success')
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make('Meniuri active', (string) Menu::query()->where('is_active', true)->count())
                ->description('Din cele 12 meniuri planificate')
                ->descriptionIcon(Heroicon::OutlinedBookOpen)
                ->color('warning')
                ->icon(Heroicon::OutlinedBookOpen),
        ];
    }
}
