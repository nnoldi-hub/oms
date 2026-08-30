<?php

namespace App\Filament\Resources\DailyMeals\Schemas;

use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DailyMealForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('meal_date')
                    ->label('Data mesei')
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() || auth()->user()?->isCoordinator())
                    ->required(),
                Select::make('week_id')
                    ->label('Saptamana')
                    ->relationship('week', 'week_number')
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() || auth()->user()?->isCoordinator())
                    ->required(),
                Select::make('congregation_id')
                    ->label('Congregatie responsabila')
                    ->relationship('congregation', 'name')
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() || auth()->user()?->isCoordinator())
                    ->required(),
                Select::make('menu_id')
                    ->label('Fel principal')
                    ->relationship(
                        name: 'menu',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                            ->where('type', 'main')
                            ->whereHas('congregations', fn (Builder $congregationQuery) => $congregationQuery->whereKey($get('congregation_id'))),
                    )
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() ?? false),
                Select::make('soup_menu_id')
                    ->label('Ciorba saptamanii')
                    ->helperText('Alege ciorba doar pentru una dintre zilele saptamanii.')
                    ->relationship(
                        name: 'soupMenu',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                            ->where('type', 'soup')
                            ->whereHas('congregations', fn (Builder $congregationQuery) => $congregationQuery->whereKey($get('congregation_id'))),
                    )
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() ?? false),
                TextInput::make('estimated_people')
                    ->label('Numar estimat persoane')
                    ->disabled(fn (): bool => auth()->user()?->isCoordinator() ?? false)
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('notes')
                    ->label('Observatii')
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() || auth()->user()?->isCoordinator())
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Stare')
                    ->options([
                        'draft' => 'Ciorna',
                        'ready_for_review' => 'Gata pentru revizuire',
                        'published' => 'Publicata',
                    ])
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() || auth()->user()?->isCoordinator())
                    ->required()
                    ->default('draft'),
            ]);
    }
}
