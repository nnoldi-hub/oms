<?php

namespace App\Filament\Widgets;

use App\Models\FoodFundTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FoodFundStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $income = (float) FoodFundTransaction::query()->where('type', 'income')->sum('amount');
        $expense = (float) FoodFundTransaction::query()->where('type', 'expense')->sum('amount');
        $balance = $income - $expense;

        return [
            Stat::make('Fonduri primite', number_format($income, 2, '.', ' ').' RON')
                ->description('Total incasari inregistrate')
                ->color('success'),
            Stat::make('Sume cheltuite / oferite', number_format($expense, 2, '.', ' ').' RON')
                ->description('Plati si avansuri inregistrate')
                ->color('danger'),
            Stat::make('Fonduri disponibile', number_format($balance, 2, '.', ' ').' RON')
                ->description($balance >= 0 ? 'Sold curent' : 'Atentie: sold negativ')
                ->color($balance >= 0 ? 'primary' : 'danger'),
        ];
    }
}
