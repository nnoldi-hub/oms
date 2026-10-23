<?php

namespace App\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyContribution extends Model
{
    protected $fillable = [
        'congregation_id', 'supply_item_id', 'delivery_date', 'quantity',
        'delivered_quantity', 'responsible_name', 'delivery_status', 'delivered_at',
        'received_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['delivery_date' => 'date', 'quantity' => 'decimal:3', 'delivered_quantity' => 'decimal:3', 'delivered_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (SupplyContribution $contribution): void {
            $user = auth()->user();

            // Coordonatorul / responsabilul inregistreaza doar contributiile congregatiei sale.
            if ($user?->isScopedToCongregation()) {
                if ($contribution->exists && (int) $contribution->getOriginal('congregation_id') !== (int) $user->congregation_id) {
                    throw new AuthorizationException('Poti gestiona doar contributiile congregatiei tale.');
                }

                $contribution->congregation_id = $user->congregation_id;
            }
        });
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    public function supplyItem(): BelongsTo
    {
        return $this->belongsTo(SupplyItem::class);
    }
}
