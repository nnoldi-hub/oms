<?php

namespace App\Filament\Resources\DailyMeals\Tables;

use App\Filament\Exports\DailyMealExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DailyMealsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meal_date')
                    ->label('Data mesei')
                    ->date()
                    ->sortable(),
                TextColumn::make('week.id')
                    ->label('Saptamana')
                    ->searchable(),
                TextColumn::make('congregation.name')
                    ->label('Congregatie')
                    ->searchable(),
                TextColumn::make('menu.name')
                    ->label('Fel principal')
                    ->searchable(),
                TextColumn::make('soupMenu.name')
                    ->label('Ciorba')
                    ->searchable(),
                TextColumn::make('estimated_people')
                    ->label('Persoane estimate')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('has_allergies')
                    ->label('Alergii')
                    ->boolean()
                    ->trueColor('danger'),
                TextColumn::make('status')
                    ->label('Stare')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('week_id')
                    ->label('Saptamana')
                    ->relationship('week', 'week_number'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->label('Exporta zile selectate')
                        ->exporter(DailyMealExporter::class),
                ]),
            ]);
    }
}
