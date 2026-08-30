<?php

namespace App\Filament\Resources\Congregations;

use App\Filament\Resources\Congregations\Pages\CreateCongregation;
use App\Filament\Resources\Congregations\Pages\EditCongregation;
use App\Filament\Resources\Congregations\Pages\ListCongregations;
use App\Filament\Resources\Congregations\Pages\ViewCongregation;
use App\Filament\Resources\Congregations\Schemas\CongregationForm;
use App\Filament\Resources\Congregations\Schemas\CongregationInfolist;
use App\Filament\Resources\Congregations\Tables\CongregationsTable;
use App\Models\Congregation;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CongregationResource extends Resource
{
    protected static ?string $model = Congregation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Congregatii';

    protected static string|\UnitEnum|null $navigationGroup = 'Administrare';

    protected static ?string $modelLabel = 'congregatie';

    protected static ?string $pluralModelLabel = 'congregatii';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CongregationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CongregationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CongregationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isCoordinator()) {
            $query->whereKey($user->congregation_id);
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
            'index' => ListCongregations::route('/'),
            'create' => CreateCongregation::route('/create'),
            'view' => ViewCongregation::route('/{record}'),
            'edit' => EditCongregation::route('/{record}/edit'),
        ];
    }
}
