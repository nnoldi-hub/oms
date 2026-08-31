<?php

namespace App\Filament\Resources\Menus\Schemas;

use App\Models\Menu;
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
                        TextInput::make('name')
                            ->label('Ingredient')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('quantity_per_person')
                            ->label('Cantitate per persoana')
                            ->numeric()
                            ->minValue(0.001)
                            ->required(),
                        Select::make('unit')
                            ->label('Unitate')
                            ->options([
                                'kg' => 'kg',
                                'g' => 'g',
                                'l' => 'l',
                                'buc' => 'buc',
                            ])
                            ->required(),
                        TextInput::make('estimated_unit_cost')
                            ->label('Pret estimat / unitate')
                            ->helperText('RON pentru kg, l sau buc')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('RON'),
                    ])
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
