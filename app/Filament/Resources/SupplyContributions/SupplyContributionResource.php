<?php

namespace App\Filament\Resources\SupplyContributions;

use App\Models\SupplyContribution;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplyContributionResource extends Resource
{
    protected static ?string $model = SupplyContribution::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;
    protected static ?string $navigationLabel = 'Contributii congregatii';
    protected static string|\UnitEnum|null $navigationGroup = 'Aprovizionare';
    protected static ?string $modelLabel = 'contributie';
    protected static ?string $pluralModelLabel = 'contributii';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('congregation_id')->label('Congregatie')->relationship('congregation', 'name')->searchable()->preload()->required(),
            Select::make('supply_item_id')->label('Resursa')->relationship('supplyItem', 'name')->searchable()->preload()->required(),
            DatePicker::make('delivery_date')->label('Ziua livrarii')->default(today())->required(),
            TextInput::make('quantity')->label('Cantitate')->numeric()->minValue(0)->required(),
            TextInput::make('responsible_name')->label('Responsabil')->maxLength(120),
            Select::make('delivery_status')->label('Status livrare')->options([
                'confirmed' => 'Confirmat',
                'in_transit' => 'In drum',
                'delivered' => 'Livrat',
            ])->required(),
            Textarea::make('notes')->label('Observatii')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('delivery_date')->label('Data')->date()->sortable(),
            TextColumn::make('congregation.name')->label('Congregatie')->searchable()->sortable(),
            TextColumn::make('supplyItem.name')->label('Resursa')->searchable(),
            TextColumn::make('quantity')->label('Cantitate')->numeric(3),
            TextColumn::make('delivery_status')->label('Status')->badge(),
            TextColumn::make('responsible_name')->label('Responsabil'),
        ])->defaultSort('delivery_date', 'desc')->recordActions([
            EditAction::make(),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplyContributions::route('/'),
            'create' => Pages\CreateSupplyContribution::route('/create'),
            'edit' => Pages\EditSupplyContribution::route('/{record}/edit'),
        ];
    }
}
