<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\FoodFundTransaction;

/**
 * Leagă donațiile în bani de registrul fondului alimentar și construiește
 * situația folosită în pagina de buget: încasări, cheltuieli, sold și explicații.
 */
class DonationFundService
{
    /**
     * O donație în bani primită devine o încasare în fond. Dacă donația este
     * promisă/anulată sau nu e în bani, încasarea legată este eliminată.
     */
    public function syncIncome(Donation $donation): void
    {
        $incomeQuery = FoodFundTransaction::query()
            ->where('donation_id', $donation->id)
            ->where('type', 'income');

        $shouldHaveIncome = $donation->kind === Donation::KIND_MONEY
            && in_array($donation->status, [Donation::STATUS_RECEIVED, Donation::STATUS_USED], true)
            && (float) $donation->amount_ron > 0;

        if (! $shouldHaveIncome) {
            $incomeQuery->delete();

            return;
        }

        $attributes = $this->incomeAttributes($donation);
        $income = $incomeQuery->first();

        if ($income !== null) {
            $income->update($attributes);

            return;
        }

        FoodFundTransaction::create(array_merge($attributes, [
            'type' => 'income',
            'donation_id' => $donation->id,
        ]));
    }

    /**
     * La ștergerea unei donații păstrăm cheltuielile, dar le deconectăm, astfel
     * încât istoricul financiar rămâne vizibil fără să mai conteze în sold.
     */
    public function deleteLinks(Donation $donation): void
    {
        FoodFundTransaction::query()
            ->where('donation_id', $donation->id)
            ->where('type', 'expense')
            ->update(['donation_id' => null]);

        FoodFundTransaction::query()
            ->where('donation_id', $donation->id)
            ->delete();
    }

    public function spentFrom(Donation $donation, ?FoodFundTransaction $exclude = null): float
    {
        return round((float) FoodFundTransaction::query()
            ->where('donation_id', $donation->id)
            ->where('type', 'expense')
            ->when($exclude !== null, fn ($query) => $query->whereKeyNot($exclude->getKey()))
            ->sum('amount'), 2);
    }

    public function availableAmount(Donation $donation, ?FoodFundTransaction $exclude = null): float
    {
        return round(max(0.0, (float) $donation->amount_ron - $this->spentFrom($donation, $exclude)), 2);
    }

    /**
     * @return array<int|string, string>
     */
    public function expenseOptions(): array
    {
        return Donation::query()
            ->visibleTo(auth()->user())
            ->money()
            ->received()
            ->where('amount_ron', '>', 0)
            ->orderByDesc('date')
            ->get()
            ->mapWithKeys(function (Donation $donation): array {
                $label = sprintf(
                    '%s · %s RON primită · %s RON disponibil',
                    $this->donationLabel($donation),
                    number_format((float) $donation->amount_ron, 2, '.', ' '),
                    number_format($this->availableAmount($donation), 2, '.', ' '),
                );

                return [$donation->id => $label];
            })
            ->all();
    }

    public function donationLabel(Donation $donation): string
    {
        return $donation->fundLabel();
    }

    /**
     * @return array{income: float, expense: float, balance: float, transactions: list<array<string, mixed>>, donations: list<array<string, mixed>>}
     */
    public function statement(): array
    {
        $transactions = FoodFundTransaction::query()
            ->visibleTo(auth()->user())
            ->with(['donation', 'recordedBy'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        $income = round((float) $transactions->where('type', 'income')->sum('amount'), 2);
        $expense = round((float) $transactions->where('type', 'expense')->sum('amount'), 2);

        $rows = $transactions->map(function (FoodFundTransaction $transaction): array {
            $signedAmount = ($transaction->type === 'income' ? 1 : -1) * (float) $transaction->amount;

            return [
                'id' => $transaction->id,
                'date' => $transaction->transaction_date,
                'type' => $transaction->type,
                'type_label' => $transaction->type === 'income' ? 'Încasare' : 'Cheltuială',
                'amount' => (float) $transaction->amount,
                'signed_amount' => $signedAmount,
                'category' => $this->categoryLabel($transaction->category),
                'counterparty' => $transaction->counterparty,
                'description' => $transaction->description,
                'donation' => $transaction->donation !== null ? $this->donationLabel($transaction->donation) : null,
                'recorded_by' => $transaction->recordedBy?->name,
            ];
        })->all();

        $donationRows = Donation::query()
            ->visibleTo(auth()->user())
            ->money()
            ->received()
            ->where('amount_ron', '>', 0)
            ->with('donorCongregation')
            ->orderByDesc('date')
            ->get()
            ->map(function (Donation $donation): array {
                $received = round((float) $donation->amount_ron, 2);
                $spent = $this->spentFrom($donation);

                return [
                    'id' => $donation->id,
                    'date' => $donation->date,
                    'label' => $this->donationLabel($donation),
                    'description' => $donation->description,
                    'received' => $received,
                    'spent' => $spent,
                    'remaining' => round(max(0.0, $received - $spent), 2),
                ];
            })
            ->all();

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => round($income - $expense, 2),
            'transactions' => $rows,
            'donations' => $donationRows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function incomeAttributes(Donation $donation): array
    {
        $parts = array_filter([
            'Donație în bani primită',
            filled($donation->donor_name) ? 'de la '.$donation->donor_name : null,
            filled($donation->donorCongregation?->name) ? 'din '.$donation->donorCongregation->name : null,
            $donation->description,
            $donation->notes,
        ], fn ($part): bool => filled($part));

        return [
            'transaction_date' => $donation->date?->toDateString()
                ?? $donation->donation_date?->toDateString()
                ?? today()->toDateString(),
            'amount' => $donation->amount_ron,
            'category' => 'donation',
            'counterparty' => $donation->donor_name ?: ($donation->donorCongregation?->name ?? null),
            'description' => implode(' · ', $parts),
            'recorded_by' => $donation->recorded_by,
            'congregation_id' => $donation->congregation_id,
            'daily_meal_id' => $donation->daily_meal_id,
        ];
    }

    private function categoryLabel(?string $category): string
    {
        return match ($category) {
            'donation' => 'Donație',
            'food' => 'Alimente',
            'water' => 'Apă',
            'snacks' => 'Gustări / deserturi',
            'transport' => 'Transport',
            'volunteer_contribution' => 'Contribuție voluntar',
            null, '' => '—',
            default => ucfirst((string) $category),
        };
    }
}
