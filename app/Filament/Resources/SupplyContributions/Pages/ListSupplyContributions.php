<?php

namespace App\Filament\Resources\SupplyContributions\Pages;

use App\Filament\Resources\SupplyContributions\SupplyContributionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplyContributions extends ListRecords
{
    protected static string $resource = SupplyContributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Adauga contributie'),
        ];
    }
}
