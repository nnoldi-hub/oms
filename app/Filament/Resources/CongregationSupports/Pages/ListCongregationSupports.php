<?php

namespace App\Filament\Resources\CongregationSupports\Pages;

use App\Filament\Resources\CongregationSupports\CongregationSupportResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListCongregationSupports extends ListRecords
{
    protected static string $resource = CongregationSupportResource::class;

    protected function getHeaderActions(): array
    {
        $params = request()->only(['from', 'to']);

        return [
            Action::make('raportPrintabil')
                ->label('Raport printabil')
                ->icon('heroicon-o-printer')
                ->url(route('congregation-support-reports.show', $params))
                ->openUrlInNewTab(),
            Action::make('raportPdf')
                ->label('Descarca PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(route('congregation-support-reports.pdf', $params))
                ->openUrlInNewTab(),
        ];
    }
}
