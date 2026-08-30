<?php

namespace App\Filament\Exports;

use App\Models\Volunteer;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class VolunteerExporter extends Exporter
{
    protected static ?string $model = Volunteer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('dailyMeal.meal_date')->label('Data mesei'),
            ExportColumn::make('dailyMeal.week.week_number')->label('Saptamana'),
            ExportColumn::make('name')->label('Nume'),
            ExportColumn::make('phone')->label('Telefon'),
            ExportColumn::make('role')->label('Responsabilitate'),
            ExportColumn::make('has_allergies')->label('Are alergii'),
            ExportColumn::make('allergy_details')->label('Detalii alergii'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Exportul voluntarilor s-a finalizat: ' . Number::format($export->successful_rows) . ' randuri exportate.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' randuri nu au putut fi exportate.';
        }

        return $body;
    }
}
