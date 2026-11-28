<?php

namespace App\Filament\Resources\FoodFundTransactions\Pages;

use App\Filament\Resources\FoodFundTransactions\FoodFundTransactionResource;
use App\Models\FoodFundTransaction;
use Filament\Resources\Pages\EditRecord;

class EditFoodFundTransaction extends EditRecord
{
    protected static string $resource = FoodFundTransactionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Legatura unei incasari automate nu se modifica din formular.
        if (($data['type'] ?? null) === 'income' && $this->record instanceof FoodFundTransaction) {
            $data['donation_id'] = $this->record->donation_id;
        }

        return $data;
    }
}
