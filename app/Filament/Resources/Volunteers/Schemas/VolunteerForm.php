<?php

namespace App\Filament\Resources\Volunteers\Schemas;

use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VolunteerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('daily_meal_id')
                    ->label('Zi de masa')
                    ->relationship(
                        name: 'dailyMeal',
                        titleAttribute: 'meal_date',
                        modifyQueryUsing: function (Builder $query): Builder {
                            $user = auth()->user();

                            return $user?->isCoordinator()
                                ? $query->where('congregation_id', $user->congregation_id)
                                : $query;
                        },
                    )
                    ->required(),
                TextInput::make('name')
                    ->label('Nume')
                    ->required(),
                TextInput::make('phone')
                    ->label('Telefon')
                    ->tel(),
                TextInput::make('role')
                    ->label('Responsabilitate')
                    ->required(),
                Toggle::make('has_allergies')
                    ->label('Are alergii')
                    ->required(),
                Textarea::make('allergy_details')
                    ->label('Detalii alergii')
                    ->required(fn (Get $get): bool => $get('has_allergies'))
                    ->visible(fn (Get $get): bool => $get('has_allergies'))
                    ->columnSpanFull(),
            ]);
    }
}
