<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nume'),
                TextEntry::make('email')
                    ->label('Email'),
                TextEntry::make('email_verified_at')
                    ->label('Email verificat la')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('congregation.name')
                    ->label('Congregatie')
                    ->placeholder('-'),
                TextEntry::make('role')
                    ->label('Rol'),
                TextEntry::make('created_at')
                    ->label('Creat la')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Actualizat la')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
