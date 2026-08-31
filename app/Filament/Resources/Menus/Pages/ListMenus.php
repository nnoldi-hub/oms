<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Filament\Resources\Menus\MenuResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recipe-cost-report')
                ->label('Raport retete si costuri')
                ->url(route('menu-cost-reports.show'))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
