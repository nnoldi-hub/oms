<?php

namespace App\Filament\Resources\SupplyConsumptions;

use App\Models\SupplyConsumption;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplyConsumptionResource extends Resource
{
    protected static ?string $model = SupplyConsumption::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static ?string $navigationLabel = 'Consum real vs estimat';
    protected static string|\UnitEnum|null $navigationGroup = 'Aprovizionare';
    protected static ?string $modelLabel = 'consum';
    protected static ?string $pluralModelLabel = 'consumuri';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->canManageSupply() ?? false) || ($user?->isProjectSupervisor() ?? false);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('daily_supply_plan_id')->label('Plan zilnic')->relationship('dailySupplyPlan', 'plan_date')->searchable()->preload()->required(),
            Select::make('supply_item_id')->label('Consumabil')->relationship('supplyItem', 'name')->searchable()->preload()->required(),
            TextInput::make('estimated_quantity')->label('Estimat')->numeric()->minValue(0)->required(),
            TextInput::make('actual_quantity')->label('Consum real')->numeric()->minValue(0)->required(),
            TextInput::make('waste_quantity')->label('Pierderi / surplus')->numeric()->minValue(0)->required(),
            Textarea::make('notes')->label('Observatii')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('dailySupplyPlan.plan_date')->label('Data')->date()->sortable(),
            TextColumn::make('supplyItem.name')->label('Consumabil')->searchable(),
            TextColumn::make('estimated_quantity')->label('Estimat')->numeric(3),
            TextColumn::make('actual_quantity')->label('Real')->numeric(3),
            TextColumn::make('variance')->label('Diferenta')->numeric(3)->state(fn (SupplyConsumption $record): float => $record->variance()),
            TextColumn::make('waste_quantity')->label('Pierderi')->numeric(3),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplyConsumptions::route('/'),
            'create' => Pages\CreateSupplyConsumption::route('/create'),
            'edit' => Pages\EditSupplyConsumption::route('/{record}/edit'),
        ];
    }
}
