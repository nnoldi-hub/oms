<?php

namespace App\Filament\Resources\DailyMeals\Pages;

use App\Filament\Resources\DailyMeals\DailyMealResource;
use App\Filament\Resources\DailyMeals\Pages\Concerns\ConfirmsSchedulingWarnings;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDailyMeal extends EditRecord
{
    use ConfirmsSchedulingWarnings;

    protected static string $resource = DailyMealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        $candidate = clone $this->getRecord();
        $candidate->fill($this->form->getRawState());

        if (! $this->acknowledgesSchedulingWarnings($candidate)) {
            return;
        }

        parent::save($shouldRedirect, $shouldSendSavedNotification);

        $this->acknowledgedSchedulingWarnings = null;
    }
}
