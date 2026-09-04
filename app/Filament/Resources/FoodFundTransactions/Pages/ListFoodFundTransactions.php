<?php

namespace App\Filament\Resources\FoodFundTransactions\Pages;

use App\Filament\Resources\FoodFundTransactions\FoodFundTransactionResource;
use App\Filament\Widgets\FoodFundStatsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
