<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    /**
     * Etichetele rolurilor, folosite în formular, în lista de conturi și în pagina de detalii.
     * Cheile sunt valorile din baza de date și nu se schimbă.
     *
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return [
            'admin' => 'Administrator',
            'coordinator' => 'Coordonator',
            'construction' => 'Echipa constructii',
            'kitchen' => 'Echipa gastronomica',
            'supply_manager' => 'Responsabil aprovizionare',
            'congregation_responsible' => 'Responsabil congregatie',
            'project_supervisor' => 'Supraveghetor proiect',
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nume')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Parola')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('role')
                    ->label('Rol')
                    ->options(self::roleOptions())
                    ->default('coordinator')
                    ->required()
                    ->live(),
                Select::make('congregation_id')
                    ->label('Congregatie')
                    ->relationship('congregation', 'name')
                    ->required(fn (Get $get): bool => $get('role') !== 'admin'),
            ]);
    }
}