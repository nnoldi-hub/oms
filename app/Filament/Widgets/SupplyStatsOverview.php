<?php

namespace App\Filament\Widgets;

use App\Models\DailySupplyPlan;
use App\Models\SupplyContribution;
use App\Models\SupplyItem;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SupplyStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = today();
        $plan = DailySupplyPlan::query()->whereDate('plan_date', $today)->first();
        $congregations = SupplyContribution::query()
            ->whereDate('delivery_date', $today)
            ->with('congregation')
            ->get()
            ->pluck('congregation.name')
            ->unique()
            ->values();
        $alerts = SupplyItem::query()->where('is_active', true)->get()->filter->isBelowMinimum()->count();

        $waterRequired = (float) ($plan?->still_water_required ?? 0) + (float) ($plan?->mineral_water_required ?? 0);
        $waterConfirmed = (float) ($plan?->still_water_confirmed ?? 0) + (float) ($plan?->mineral_water_confirmed ?? 0);
        $snacksRequired = (float) ($plan?->snacks_required ?? 0);
        $snacksConfirmed = (float) ($plan?->snacks_confirmed ?? 0);

        return [
            Stat::make('Persoane programate azi', (string) ($plan?->people_count ?? 0))
                ->description('Planificare consumabile')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->icon(Heroicon::OutlinedUsers),
            Stat::make('Aprovizionare azi', sprintf('Apa %.0f/%.0f L | Gustari %.0f/%.0f', $waterRequired, $waterConfirmed, $snacksRequired, $snacksConfirmed))
                ->description('Necesar / confirmat')
                ->descriptionIcon(Heroicon::OutlinedShoppingCart)
                ->color($waterConfirmed < $waterRequired || $snacksConfirmed < $snacksRequired ? 'warning' : 'success'),
            Stat::make('Congregatii cu resurse azi', (string) $congregations->count())
                ->description($congregations->join(', ') ?: 'Nicio livrare inregistrata')
                ->descriptionIcon(Heroicon::OutlinedBuildingOffice2),
            Stat::make('Alerte stoc', (string) $alerts)
                ->description('Consumabile sub nivelul minim')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($alerts > 0 ? 'danger' : 'success'),
        ];
    }
}
