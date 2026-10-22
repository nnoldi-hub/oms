<?php

namespace App\Filament\Resources\DailyMeals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DailyMealForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('meal_date')
                    ->label('Data mesei')
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() || auth()->user()?->isCoordinator())
                    ->required(),
                Select::make('week_id')
                    ->label('Saptamana')
                    ->relationship('week', 'week_number')
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() || auth()->user()?->isCoordinator())
                    ->required(),
                Select::make('congregation_id')
                    ->label('Congregatie responsabila')
                    ->relationship('congregation', 'name')
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() || auth()->user()?->isCoordinator())
                    ->required(),
                Select::make('menu_id')
                    ->label('Fel principal')
                    ->relationship(
                        'menu',
                        'name',
                        fn (Builder $query): Builder => $query->where('type', 'main')->orderBy('name'),
                    )
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() ?? false),
                Select::make('soup_menu_id')
                    ->label('Ciorba')
                    ->helperText('Recomandat: a doua si a patra masa a saptamanii, doua ciorbe diferite. Maximum 2 pe saptamana (avertisment).')
                    ->relationship(
                        'soupMenu',
                        'name',
                        fn (Builder $query): Builder => $query->where('type', 'soup')->orderBy('name'),
                    )
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() ?? false),
                Select::make('dessert_menu_id')
                    ->label('Desert / gustare')
                    ->relationship(
                        'dessertMenu',
                        'name',
                        fn (Builder $query): Builder => $query->where('type', 'dessert')->orderBy('name'),
                    )
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() ?? false),
                TextInput::make('estimated_people')
                    ->label('Numar estimat persoane')
                    ->disabled(fn (): bool => auth()->user()?->isCoordinator() ?? false)
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('confirmed_people')
                    ->label('Numar confirmat persoane')
                    ->helperText('Completeaza cand numarul final e confirmat; rapoartele il afiseaza in locul estimarii.')
                    ->disabled(fn (): bool => auth()->user()?->isCoordinator() ?? false)
                    ->numeric()
                    ->integer()
                    ->minValue(0),
                TextInput::make('maximum_budget')
                    ->label('Buget maxim (RON)')
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() ?? false)
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01),
                TextInput::make('contributor_count')
                    ->label('Persoane care contribuie')
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() ?? false)
                    ->numeric()
                    ->integer()
                    ->minValue(1),
                Toggle::make('is_cooked')
                    ->label('S-a gatit')
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                    ->columnSpanFull(),
                Select::make('actual_menu_id')
                    ->label('Fel principal gatit de fapt')
                    ->helperText('Completeaza doar daca s-a gatit altceva decat era planificat.')
                    ->relationship(
                        'actualMenu',
                        'name',
                        fn (Builder $query): Builder => $query->where('type', 'main')->orderBy('name'),
                    )
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                TextInput::make('actual_cost')
                    ->label('Cost real al zilei (RON)')
                    ->helperText('Are prioritate fata de costul estimat din retete.')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                Textarea::make('notes')
                    ->label('Observatii')
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() || auth()->user()?->isCoordinator())
                    ->columnSpanFull(),
                TextInput::make('delivery_time')
                    ->label('Ora de livrare pe santier')
                    ->helperText('Camp liber: ora la care masa trebuie livrata pe santier (de exemplu 11:30).')
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() || auth()->user()?->isCoordinator())
                    ->maxLength(120),
                Select::make('status')
                    ->label('Stare')
                    ->options([
                        'draft' => 'Ciorna',
                        'ready_for_review' => 'Gata pentru revizuire',
                        'published' => 'Publicata',
                    ])
                    ->disabled(fn (): bool => auth()->user()?->isConstructionTeam() || auth()->user()?->isCoordinator())
                    ->required()
                    ->default('draft'),
            ]);
    }
}
