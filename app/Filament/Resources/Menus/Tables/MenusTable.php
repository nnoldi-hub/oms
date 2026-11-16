<?php

namespace App\Filament\Resources\Menus\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tip')
                    ->visibleFrom('md')
                    ->formatStateUsing(fn (string $state): string => [
                        'main' => 'Fel principal',
                        'soup' => 'Ciorba',
                        'dessert' => 'Desert / gustare',
                    ][$state] ?? $state),
                TextColumn::make('packaging_cost')
                    ->money('RON')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('is_reviewed')
                    ->label('Verificare')
                    ->badge()
                    ->state(fn ($record): string => $record->is_reviewed ? 'verificata' : 'nouă, de verificat')
                    ->color(fn ($state): string => $state === 'verificata' ? 'success' : 'warning'),
                IconColumn::make('is_active')
                    ->boolean(),
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
                SelectFilter::make('type')
                    ->label('Categorie')
                    ->options([
                        'main' => 'Fel principal',
                        'soup' => 'Ciorbe',
                        'dessert' => 'Desert / gustare',
                    ]),
                SelectFilter::make('is_reviewed')
                    ->label('Verificare')
                    ->options([
                        '1' => 'Verificate',
                        '0' => 'Noi, de verificat',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
