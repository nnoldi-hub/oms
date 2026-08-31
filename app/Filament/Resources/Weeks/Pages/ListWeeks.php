<?php

namespace App\Filament\Resources\Weeks\Pages;

use App\Filament\Resources\Weeks\WeekResource;
use App\Models\Congregation;
use App\Services\ScheduleGenerator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;

class ListWeeks extends ListRecords
{
    protected static string $resource = WeekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateSchedule')
                ->label('Genereaza planificare')
                ->icon('heroicon-o-calendar-days')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->form([
                    DatePicker::make('start_date')->label('Data de inceput')->required()->native(false),
                    TextInput::make('weeks_count')->label('Numar saptamani')->numeric()->integer()->minValue(1)->maxValue(52)->default(16)->required(),
                    Select::make('congregation_ids')
                        ->label('Congregatii, in ordinea rotatiei')
                        ->options(fn (): array => Congregation::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->multiple()
                        ->minItems(3)
                        ->maxItems(3)
                        ->required()
                        ->helperText('Ultima saptamana se imparte automat 2 zile / 2 zile / 1 zi, in aceasta ordine.'),
                ])
                ->requiresConfirmation()
                ->modalDescription('Se vor crea numai saptamani si zile de masa goale. Retetele, portiile si bugetele se configureaza ulterior.')
                ->action(function (array $data): void {
                    app(ScheduleGenerator::class)->generate(
                        $data['start_date'],
                        (int) $data['weeks_count'],
                        array_map('intval', $data['congregation_ids']),
                    );
                })
                ->successNotificationTitle('Planificarea a fost generata.'),
            CreateAction::make(),
        ];
    }
}
