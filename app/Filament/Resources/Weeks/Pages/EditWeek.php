<?php

namespace App\Filament\Resources\Weeks\Pages;

use App\Filament\Resources\Weeks\WeekResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWeek extends EditRecord
{
    protected static string $resource = WeekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
