<?php

namespace App\Filament\Resources\DailyMeals\Pages;

use App\Filament\Resources\DailyMeals\DailyMealResource;
use App\Filament\Resources\DailyMeals\Pages\Concerns\ConfirmsSchedulingWarnings;
use App\Models\DailyMeal;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyMeal extends CreateRecord
{
    use ConfirmsSchedulingWarnings;

    protected static string $resource = DailyMealResource::class;

    public function create(bool $another = false): void
    {
        $candidate = new DailyMeal($this->form->getRawState());

        if (! $this->acknowledgesSchedulingWarnings($candidate)) {
            return;
        }

        parent::create($another);

        $this->acknowledgedSchedulingWarnings = null;
    }
}
