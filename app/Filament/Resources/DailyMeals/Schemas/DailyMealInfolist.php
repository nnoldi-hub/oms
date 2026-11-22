<?php

namespace App\Filament\Resources\DailyMeals\Schemas;

use App\Filament\Resources\DailyMeals\DailyMealResource;
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
                TextEntry::make('week.week_number')
                    ->label('Saptamana'),
                TextEntry::make('menu.name')
                    ->label('Fel principal')
                    ->placeholder('-'),
                TextEntry::make('soupMenu.name')
                    ->label('Ciorba')
                    ->placeholder('-'),
                TextEntry::make('dessertMenu.name')
                    ->label('Desert / gustare')
                    ->placeholder('-'),
                TextEntry::make('estimated_people')
                    ->label('Numar estimat persoane')
                    ->numeric()
                    ->visible(fn (DailyMeal $record): bool => DailyMealResource::canViewSensitiveFields($record)),
                IconEntry::make('is_cooked')
                    ->label('S-a gatit')
                    ->boolean()
                    ->trueColor('success'),
                TextEntry::make('actualMenu.name')
                    ->label('Fel gatit de fapt')
                    ->placeholder('La fel ca cel planificat'),
                TextEntry::make('actual_cost')
                    ->label('Cost real al zilei (RON)')
                    ->money('RON')
                    ->placeholder('-')
                    ->visible(fn (DailyMeal $record): bool => DailyMealResource::canViewSensitiveFields($record)),
                IconEntry::make('has_allergies')
                    ->label('Alergii declarate')
                    ->state(fn (DailyMeal $record): bool => $record->volunteers()->where('has_allergies', true)->exists())
                    ->boolean()
                    ->trueColor('danger')
                    ->visible(fn (DailyMeal $record): bool => DailyMealResource::canViewSensitiveFields($record)),
                TextEntry::make('notes')
                    ->label('Observatii')
                    ->placeholder('-')
                    ->columnSpanFull()
                    ->visible(fn (DailyMeal $record): bool => DailyMealResource::canViewSensitiveFields($record)),
                TextEntry::make('status')
                    ->label('Stare'),
                TextEntry::make('created_at')
                    ->label('Creat la')
                    ->dateTime()
                    ->placeholder('-')
                    ->visible(fn (DailyMeal $record): bool => DailyMealResource::canViewSensitiveFields($record)),
                TextEntry::make('updated_at')
                    ->label('Actualizat la')
                    ->dateTime()
                    ->placeholder('-')
                    ->visible(fn (DailyMeal $record): bool => DailyMealResource::canViewSensitiveFields($record)),
            ]);
    }
}
