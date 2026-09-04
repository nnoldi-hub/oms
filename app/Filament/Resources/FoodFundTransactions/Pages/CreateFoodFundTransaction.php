<?php

namespace App\Filament\Resources\FoodFundTransactions\Pages;

use App\Filament\Resources\FoodFundTransactions\FoodFundTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFoodFundTransaction extends CreateRecord
{
    protected static string $resource = FoodFundTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();

        return $data;
    }
}
