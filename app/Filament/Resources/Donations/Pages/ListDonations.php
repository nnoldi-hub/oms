<?php

namespace App\Filament\Resources\Donations\Pages;

use App\Filament\Resources\Donations\DonationResource;
use App\Filament\Widgets\DonationStatsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDonations extends ListRecords
{
    protected static string $resource = DonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Adaugă donație'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DonationStatsOverview::class,
        ];
    }
}
