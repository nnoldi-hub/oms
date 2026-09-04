<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyConsumption extends Model
{
    protected $fillable = [
        'daily_supply_plan_id', 'supply_item_id', 'estimated_quantity',
        'actual_quantity', 'waste_quantity', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_quantity' => 'decimal:3',
            'actual_quantity' => 'decimal:3',
            'waste_quantity' => 'decimal:3',
        ];
    }

    public function dailySupplyPlan(): BelongsTo
    {
        return $this->belongsTo(DailySupplyPlan::class);
    }

    public function supplyItem(): BelongsTo
    {
        return $this->belongsTo(SupplyItem::class);
    }

    public function variance(): float
    {
        return round((float) $this->actual_quantity - (float) $this->estimated_quantity, 3);
    }
}
