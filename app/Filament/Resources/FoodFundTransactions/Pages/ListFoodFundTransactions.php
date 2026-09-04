<?php

namespace App\Filament\Resources\FoodFundTransactions\Pages;

use App\Filament\Resources\FoodFundTransactions\FoodFundTransactionResource;
use App\Models\FoodFundTransaction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ListFoodFundTransactions extends ListRecords
{
    protected static string $resource = FoodFundTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Adauga operatiune'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FoodFundStatsOverview::class,
        ];
    }
}

class FoodFundStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $income = (float) FoodFundTransaction::query()->income()->sum('amount');
        $expense = (float) FoodFundTransaction::query()->expense()->sum('amount');
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
