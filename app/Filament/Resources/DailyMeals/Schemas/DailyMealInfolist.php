<?php

namespace App\Filament\Resources\DailyMeals\Schemas;

use App\Models\DailyMeal;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DailyMealInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('meal_date')
                    ->label('Data mesei')
                    ->date(),
                TextEntry::make('week.id')
                    ->label('Saptamana'),
                TextEntry::make('menu.name')
                    ->label('Fel principal')
                    ->placeholder('-'),
                TextEntry::make('soupMenu.name')
                    ->label('Ciorba saptamanii')
                    ->placeholder('-'),
                TextEntry::make('dessertMenu.name')
                    ->label('Desert / gustare')
                    ->placeholder('-'),
                TextEntry::make('estimated_people')
                    ->label('Numar estimat persoane')
                    ->numeric(),
                IconEntry::make('has_allergies')
                    ->label('Alergii declarate')
                    ->state(fn (DailyMeal $record): bool => $record->volunteers()->where('has_allergies', true)->exists())
                    ->boolean()
                    ->trueColor('danger'),
                TextEntry::make('notes')
                    ->label('Observatii')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->label('Stare'),
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
