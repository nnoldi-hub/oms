<?php

namespace App\Filament\Resources\Weeks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WeekForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('week_number')
                    ->label('Numar saptamana')
                    ->required()
                    ->numeric(),
                DatePicker::make('start_date')
                    ->label('Data inceput')
                    ->required(),
                Select::make('congregation_id')
                    ->label('Congregatie principala')
                    ->relationship('congregation', 'name')
                    ->required(),
            ]);
    }
}
