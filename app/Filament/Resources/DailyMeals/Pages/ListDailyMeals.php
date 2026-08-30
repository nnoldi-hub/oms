<?php

namespace App\Filament\Resources\DailyMeals\Pages;

use App\Filament\Resources\DailyMeals\DailyMealResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDailyMeals extends ListRecords
{
    protected static string $resource = DailyMealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
