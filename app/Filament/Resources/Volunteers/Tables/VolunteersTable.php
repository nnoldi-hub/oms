<?php

namespace App\Filament\Resources\Volunteers\Tables;

use App\Filament\Exports\VolunteerExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VolunteersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dailyMeal.id')
                    ->label('Zi de masa')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nume')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Responsabilitate')
                    ->searchable(),
                IconColumn::make('has_allergies')
                    ->label('Alergii')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creat la')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizat la')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('dailyMeal.week_id')
                    ->label('Saptamana')
                    ->relationship('dailyMeal.week', 'week_number'),
                SelectFilter::make('has_allergies')
                    ->label('Doar alergii')
                    ->options([1 => 'Da', 0 => 'Nu']),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->label('Exporta voluntari selectati')
                        ->exporter(VolunteerExporter::class),
                ]),
            ]);
    }
}
