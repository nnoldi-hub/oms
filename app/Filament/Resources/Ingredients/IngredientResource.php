<?php

namespace App\Filament\Resources\Ingredients;

use App\Filament\Resources\Ingredients\Pages\CreateIngredient;
use App\Filament\Resources\Ingredients\Pages\EditIngredient;
use App\Filament\Resources\Ingredients\Pages\ListIngredients;
use App\Models\Ingredient;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IngredientResource extends Resource
{
    protected static ?string $model = Ingredient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'Ingrediente';

    protected static string|\UnitEnum|null $navigationGroup = 'Bucatarie';

    protected static ?string $modelLabel = 'ingredient';

    protected static ?string $pluralModelLabel = 'ingrediente';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Denumire ingredient')->required()->maxLength(120),
            Select::make('unit')->label('Unitate de cumparare')->options(array_combine(Ingredient::UNITS, Ingredient::UNITS))->required(),
            TextInput::make('unit_price')->label('Pret curent / unitate')->numeric()->minValue(0)->prefix('RON')->helperText('Lasa gol daca pretul nu este cunoscut inca.'),
            Toggle::make('is_active')->label('Ingredient activ')->default(true)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Ingredient')->searchable()->sortable(),
            TextColumn::make('unit')->label('Unitate')->sortable(),
            TextColumn::make('unit_price')->label('Pret curent')->money('RON')->placeholder('De configurat')->sortable(),
            IconColumn::make('is_active')->label('Activ')->boolean(),
            TextColumn::make('updated_at')->label('Actualizat')->dateTime()->sortable(),
        ])->recordActions([
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIngredients::route('/'),
            'create' => CreateIngredient::route('/create'),
            'edit' => EditIngredient::route('/{record}/edit'),
        ];
    }
}