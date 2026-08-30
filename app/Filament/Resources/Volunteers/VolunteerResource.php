<?php

namespace App\Filament\Resources\Volunteers;

use App\Filament\Resources\Volunteers\Pages\CreateVolunteer;
use App\Filament\Resources\Volunteers\Pages\EditVolunteer;
use App\Filament\Resources\Volunteers\Pages\ListVolunteers;
use App\Filament\Resources\Volunteers\Pages\ViewVolunteer;
use App\Filament\Resources\Volunteers\Schemas\VolunteerForm;
use App\Filament\Resources\Volunteers\Schemas\VolunteerInfolist;
use App\Filament\Resources\Volunteers\Tables\VolunteersTable;
use App\Models\Volunteer;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VolunteerResource extends Resource
{
    protected static ?string $model = Volunteer::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Voluntari';

    protected static string|\UnitEnum|null $navigationGroup = 'Planificare';

    protected static ?string $modelLabel = 'voluntar';

    protected static ?string $pluralModelLabel = 'voluntari';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return VolunteerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VolunteerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VolunteersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isCoordinator()) {
            $query->whereHas('dailyMeal', fn (Builder $mealQuery) => $mealQuery->where('congregation_id', $user->congregation_id));
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $startOfWeek = today()->startOfWeek();
        $endOfWeek = today()->endOfWeek();
        $query = Volunteer::query()->whereHas(
            'dailyMeal',
            fn (Builder $mealQuery) => $mealQuery->whereBetween('meal_date', [$startOfWeek, $endOfWeek]),
        );

        if ($user?->isCoordinator()) {
            $query->whereHas('dailyMeal', fn (Builder $mealQuery) => $mealQuery->where('congregation_id', $user->congregation_id));
        }

        return (string) $query->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Voluntari inscrisi in saptamana curenta';
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
            'index' => ListVolunteers::route('/'),
            'create' => CreateVolunteer::route('/create'),
            'view' => ViewVolunteer::route('/{record}'),
            'edit' => EditVolunteer::route('/{record}/edit'),
        ];
    }
}
