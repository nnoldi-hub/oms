<?php
namespace App\Filament\Resources\SupplyConsumptions\Pages;
use App\Filament\Resources\SupplyConsumptions\SupplyConsumptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListSupplyConsumptions extends ListRecords
{
    protected static string $resource = SupplyConsumptionResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()->label('Adauga consum')]; }
}
