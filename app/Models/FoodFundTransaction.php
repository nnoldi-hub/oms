<?php

namespace App\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodFundTransaction extends Model
{
    protected $fillable = [
        'type',
        'transaction_date',
        'amount',
        'category',
        'counterparty',
        'payment_method',
        'reference',
        'description',
        'recorded_by',
        'daily_meal_id',
        'congregation_id',
        'volunteer_contribution_status',
    ];

    protected static function booted(): void
    {
        static::saving(function (FoodFundTransaction $transaction): void {
            $user = auth()->user();

            // Coordonatorul inregistreaza doar pentru congregatia sa si nu poate modifica operatiunile altora.
            if ($user?->isScopedToCongregation()) {
                if ($transaction->exists && (int) $transaction->getOriginal('congregation_id') !== (int) $user->congregation_id) {
                    throw new AuthorizationException('Poti gestiona doar fondurile congregatiei tale.');
                }

                $transaction->congregation_id = $user->congregation_id;
            }

            if ($transaction->congregation_id === null && $transaction->daily_meal_id !== null) {
                $transaction->congregation_id = DailyMeal::query()->whereKey($transaction->daily_meal_id)->value('congregation_id');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    /**
     * Coordonatorul vede doar operatiunile congregatiei sale.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if ($user?->isScopedToCongregation()) {
            $query->where('congregation_id', $user->congregation_id);
        }

        return $query;
    }

    public function dailyMeal(): BelongsTo
    {
        return $this->belongsTo(DailyMeal::class);
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }
}
