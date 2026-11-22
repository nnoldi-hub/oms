<?php

namespace App\Filament\Resources\Weeks\Pages;

use App\Filament\Resources\Weeks\WeekResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWeek extends ViewRecord
{
    protected static string $resource = WeekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('raportSaptamanal')
                ->label('Raport saptamanal')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('weekly-reports.show', $this->record))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
