<?php

namespace App\Filament\Resources\Menus\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MenuInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Denumire meniu'),
                TextEntry::make('instructions')
                    ->label('Instructiuni pentru preparare')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('ingredients')
                    ->label('Ingrediente per persoana')
                    ->columnSpanFull(),
                TextEntry::make('packaging_cost')
                    ->label('Cost ambalaj per portie')
                    ->money('RON'),
                IconEntry::make('is_active')
                    ->label('Meniu activ')
                    ->boolean(),
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
