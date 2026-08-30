<?php

namespace App\Filament\Resources\DailyMeals;

use App\Filament\Resources\DailyMeals\Pages\CreateDailyMeal;
use App\Filament\Resources\DailyMeals\Pages\EditDailyMeal;
use App\Filament\Resources\DailyMeals\Pages\ListDailyMeals;
use App\Filament\Resources\DailyMeals\Pages\ViewDailyMeal;
use App\Filament\Resources\DailyMeals\Schemas\DailyMealForm;
use App\Filament\Resources\DailyMeals\Schemas\DailyMealInfolist;
use App\Filament\Resources\DailyMeals\Tables\DailyMealsTable;
use App\Models\DailyMeal;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DailyMealResource extends Resource
{
    protected static ?string $model = DailyMeal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Zile de masa';

    protected static string|\UnitEnum|null $navigationGroup = 'Planificare';

    protected static ?string $modelLabel = 'zi de masa';

    protected static ?string $pluralModelLabel = 'zile de masa';

    public static function form(Schema $schema): Schema
    {
        return DailyMealForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DailyMealInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DailyMealsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        $query->withExists([
            'volunteers as has_allergies' => fn (Builder $volunteerQuery) => $volunteerQuery->where('has_allergies', true),
        ]);

        if ($user?->isCoordinator()) {
            $query->where('congregation_id', $user->congregation_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDailyMeals::route('/'),
            'create' => CreateDailyMeal::route('/create'),
            'view' => ViewDailyMeal::route('/{record}'),
            'edit' => EditDailyMeal::route('/{record}/edit'),
        ];
    }
}
