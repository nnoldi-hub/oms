<?php

namespace App\Filament\Resources\SupplyItems;

use App\Models\SupplyItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplyItemResource extends Resource
{
    protected static ?string $model = SupplyItem::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;
    protected static ?string $navigationLabel = 'Stoc & consumabile';
    protected static string|\UnitEnum|null $navigationGroup = 'Aprovizionare';
    protected static ?string $modelLabel = 'consumabil';
    protected static ?string $pluralModelLabel = 'consumabile';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Denumire')->required()->maxLength(120),
            Select::make('category')->label('Categorie')->options([
                'snack' => 'Gustari',
                'water' => 'Apa',
                'meal' => 'Mancare',
                'auxiliary' => 'Materiale auxiliare',
            ])->required(),
            TextInput::make('unit')->label('Unitate')->required()->maxLength(20),
            TextInput::make('current_stock')->label('Stoc actual')->numeric()->minValue(0)->required(),
            TextInput::make('minimum_stock')->label('Stoc minim')->numeric()->minValue(0)->required(),
            TextInput::make('estimated_daily_consumption')->label('Consum estimat / zi')->numeric()->minValue(0)->required(),
            TextInput::make('actual_consumption')->label('Consum real cumulat')->numeric()->minValue(0)->required(),
            Toggle::make('is_active')->label('Activ')->default(true)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Consumabil')->searchable()->sortable(),
            TextColumn::make('category')->label('Categorie')->badge(),
            TextColumn::make('current_stock')->label('Stoc')->numeric(3)->suffix(fn (SupplyItem $record): string => ' '.$record->unit)->sortable(),
            TextColumn::make('minimum_stock')->label('Minim')->numeric(3)->sortable(),
            IconColumn::make('below_minimum')->label('Alerta')->boolean()->state(fn (SupplyItem $record): bool => $record->isBelowMinimum()),
            IconColumn::make('is_active')->label('Activ')->boolean(),
        ])->recordActions([
            EditAction::make(),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplyItems::route('/'),
            'create' => Pages\CreateSupplyItem::route('/create'),
            'edit' => Pages\EditSupplyItem::route('/{record}/edit'),
        ];
    }
}
