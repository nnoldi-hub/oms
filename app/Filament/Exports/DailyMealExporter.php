<?php

namespace App\Filament\Exports;

use App\Models\DailyMeal;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class DailyMealExporter extends Exporter
{
    protected static ?string $model = DailyMeal::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('meal_date')->label('Data mesei'),
            ExportColumn::make('week.week_number')->label('Saptamana'),
            ExportColumn::make('congregation.name')->label('Congregatie'),
            ExportColumn::make('menu.name')->label('Meniu'),
            ExportColumn::make('estimated_people')->label('Persoane estimate'),
            ExportColumn::make('status')->label('Stare'),
            ExportColumn::make('notes')->label('Observatii'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Exportul zilelor de masa s-a finalizat: ' . Number::format($export->successful_rows) . ' randuri exportate.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' randuri nu au putut fi exportate.';
        }

        return $body;
    }
}
