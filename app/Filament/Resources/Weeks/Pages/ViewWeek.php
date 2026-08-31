<?php

namespace App\Filament\Resources\Weeks\Pages;

use App\Filament\Resources\Weeks\WeekResource;
use App\Models\Congregation;
use App\Models\PublicCongregationWeekLink;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewWeek extends ViewRecord
{
    protected static string $resource = WeekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publicCongregationLink')
                ->label('Creeaza link public')
                ->icon('heroicon-o-link')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->form([
                    Select::make('congregation_id')
                        ->label('Congregatie')
                        ->options(fn (): array => $this->record->dailyMeals()->with('congregation')->get()->pluck('congregation.name', 'congregation_id')->filter()->unique()->all())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $link = PublicCongregationWeekLink::firstOrCreate([
                        'week_id' => $this->record->id,
                        'congregation_id' => $data['congregation_id'],
                    ]);

                    Notification::make()
                        ->title('Link public creat')
                        ->body(route('public-congregation-week-sheets.show', $link))
                        ->success()
                        ->persistent()
                        ->send();
                }),
            Action::make('raportSaptamanal')
                ->label('Raport saptamanal')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('weekly-reports.show', $this->record))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
