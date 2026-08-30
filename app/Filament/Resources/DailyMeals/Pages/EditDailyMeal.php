<?php

namespace App\Filament\Resources\DailyMeals\Pages;

use App\Filament\Resources\DailyMeals\DailyMealResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDailyMeal extends EditRecord
{
    protected static string $resource = DailyMealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
