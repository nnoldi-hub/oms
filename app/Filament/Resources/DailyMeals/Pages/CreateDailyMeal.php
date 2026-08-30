<?php

namespace App\Filament\Resources\DailyMeals\Pages;

use App\Filament\Resources\DailyMeals\DailyMealResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyMeal extends CreateRecord
{
    protected static string $resource = DailyMealResource::class;
}
