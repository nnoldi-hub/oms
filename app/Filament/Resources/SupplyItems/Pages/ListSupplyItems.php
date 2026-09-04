<?php

namespace App\Filament\Resources\SupplyItems\Pages;

use App\Filament\Resources\SupplyItems\SupplyItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplyItems extends ListRecords
{
    protected static string $resource = SupplyItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Adauga consumabil'),
        ];
    }
}
