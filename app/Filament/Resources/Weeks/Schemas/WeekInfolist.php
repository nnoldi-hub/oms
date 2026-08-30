<?php

namespace App\Filament\Resources\Weeks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WeekInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('week_number')
                    ->label('Numar saptamana')
                    ->numeric(),
                TextEntry::make('start_date')
                    ->label('Data inceput')
                    ->date(),
                TextEntry::make('congregation.name')
                    ->label('Congregatie principala'),
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
