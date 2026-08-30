<?php

namespace App\Filament\Resources\Weeks;

use App\Filament\Resources\Weeks\Pages\CreateWeek;
use App\Filament\Resources\Weeks\Pages\EditWeek;
use App\Filament\Resources\Weeks\Pages\ListWeeks;
use App\Filament\Resources\Weeks\Pages\ViewWeek;
use App\Filament\Resources\Weeks\Schemas\WeekForm;
use App\Filament\Resources\Weeks\Schemas\WeekInfolist;
use App\Filament\Resources\Weeks\Tables\WeeksTable;
use App\Models\Week;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WeekResource extends Resource
{
    protected static ?string $model = Week::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Saptamani';

    protected static string|\UnitEnum|null $navigationGroup = 'Planificare';

    protected static ?string $modelLabel = 'saptamana';

    protected static ?string $pluralModelLabel = 'saptamani';

    public static function form(Schema $schema): Schema
    {
        return WeekForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WeekInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WeeksTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isCoordinator()) {
            $query->where(function (Builder $scopedQuery) use ($user): void {
                $scopedQuery
                    ->where('congregation_id', $user->congregation_id)
                    ->orWhereHas('dailyMeals', fn (Builder $mealQuery) => $mealQuery->where('congregation_id', $user->congregation_id));
            });
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
            'index' => ListWeeks::route('/'),
            'create' => CreateWeek::route('/create'),
            'view' => ViewWeek::route('/{record}'),
            'edit' => EditWeek::route('/{record}/edit'),
        ];
    }
}
