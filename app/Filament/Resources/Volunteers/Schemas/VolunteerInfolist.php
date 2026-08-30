<?php

namespace App\Filament\Resources\Volunteers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VolunteerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('dailyMeal.id')
                    ->label('Zi de masa'),
                TextEntry::make('name')
                    ->label('Nume'),
                TextEntry::make('phone')
                    ->label('Telefon')
                    ->placeholder('-'),
                TextEntry::make('role')
                    ->label('Responsabilitate'),
                IconEntry::make('has_allergies')
                    ->label('Are alergii')
                    ->boolean(),
                TextEntry::make('allergy_details')
                    ->label('Detalii alergii')
                    ->placeholder('-')
                    ->columnSpanFull(),
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
