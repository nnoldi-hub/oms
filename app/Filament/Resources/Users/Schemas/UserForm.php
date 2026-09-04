<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
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
                    ->options([
                        'admin' => 'Administrator',
                        'coordinator' => 'Coordonator congregatie',
                        'construction' => 'Echipa constructii',
                        'kitchen' => 'Echipa gastronomica',
                        'supply_manager' => 'Responsabil aprovizionare',
                        'congregation_responsible' => 'Responsabil congregatie',
                        'project_supervisor' => 'Supraveghetor proiect',
                    ])
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
