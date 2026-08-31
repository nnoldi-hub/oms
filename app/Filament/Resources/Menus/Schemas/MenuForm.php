<?php

namespace App\Filament\Resources\Menus\Schemas;

use App\Models\Menu;
use App\Models\Ingredient;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Denumire meniu')
                    ->required(),
                Select::make('type')
                    ->label('Tip reteta')
                    ->options([
                        'main' => 'Fel principal',
                        'soup' => 'Ciorba',
                        'dessert' => 'Desert / gustare',
                    ])
                    ->default('main')
                    ->required(),
                Textarea::make('instructions')
                    ->label('Instructiuni pentru preparare')
                    ->columnSpanFull(),
                Repeater::make('ingredients')
                    ->label('Ingrediente per persoana')
                    ->schema([
                        Select::make('ingredient_id')
                            ->label('Ingredient')
                            ->required()
                            ->options(fn (): array => Ingredient::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Ingredient $ingredient): array => [$ingredient->id => "{$ingredient->name} ({$ingredient->unit})"])
                                ->all())
                            ->searchable()
                            ->preload(),
                        TextInput::make('quantity_per_person')
                            ->label('Cantitate per persoana')
                            ->numeric()
                            ->minValue(0.001)
                            ->required(),
                    ])
                    ->helperText('Alege ingredientul din catalogul global. Unitatea si pretul sunt preluate automat de acolo.')
                    ->required()
                    ->columnSpanFull(),
                CheckboxList::make('allergens')
                    ->label('Alergeni declarati')
                    ->helperText('Selecteaza alergenele cunoscute. Lasa lista goala doar dupa verificarea retetei si a etichetelor ingredientelor.')
                    ->options(array_combine(Menu::ALLERGENS, Menu::ALLERGENS))
                    ->columns(3)
                    ->columnSpanFull(),
                TextInput::make('packaging_cost')
                    ->label('Cost ambalaj per portie')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('RON'),
                Toggle::make('is_active')
                    ->label('Meniu activ')
                    ->required(),
            ]);
    }
}
