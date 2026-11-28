<?php

namespace App\Filament\Resources\FoodFundTransactions\Pages;

use App\Filament\Resources\FoodFundTransactions\FoodFundTransactionResource;
use App\Models\Donation;
use Filament\Resources\Pages\CreateRecord;

class CreateFoodFundTransaction extends CreateRecord
{
    protected static string $resource = FoodFundTransactionResource::class;

    public function mount(): void
    {
        parent::mount();

        $donationId = (int) request()->query('donation_id', 0);
        $donation = $donationId > 0
            ? Donation::query()->visibleTo(auth()->user())->find($donationId)
            : null;

        if ($donation !== null) {
            $this->form->fill([
                'type' => 'expense',
                'donation_id' => $donation->id,
                'transaction_date' => today()->toDateString(),
                'category' => 'food',
                'description' => '',
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();

        // Incasarile generate de donatii se gestioneaza din modulul Donatii.
        if (($data['type'] ?? null) === 'income') {
            $data['donation_id'] = null;
        }

        return $data;
    }
}
