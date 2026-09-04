<?php

namespace App\Models;

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

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    public function supplyItem(): BelongsTo
    {
        return $this->belongsTo(SupplyItem::class);
    }
}
