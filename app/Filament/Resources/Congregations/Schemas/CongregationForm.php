<?php

namespace App\Filament\Resources\Congregations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CongregationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Denumire')
                    ->disabled(fn (): bool => auth()->user()?->isCoordinator() ?? false)
                    ->required(),
                TextInput::make('assistant_name')
                    ->label('Nume asistent responsabil')
                    ->maxLength(120),
                TextInput::make('assistant_phone')
                    ->label('Telefon asistent')
                    ->tel()
                    ->maxLength(30),
                TextInput::make('assistant_email')
                    ->label('Email asistent')
                    ->email()
                    ->maxLength(255),
                Select::make('menus')
                    ->label('Retete aprobate pentru congregatie')
                    ->relationship('menus', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required(),
            ]);
    }
}
