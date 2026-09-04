<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplyItem extends Model
{
    use HasFactory;

    public const CATEGORIES = ['snack', 'water', 'meal', 'auxiliary'];

    protected $fillable = [
        'name', 'category', 'unit', 'unit_cost', 'current_stock', 'minimum_stock',
        'estimated_daily_consumption', 'actual_consumption', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'minimum_stock' => 'decimal:3',
            'estimated_daily_consumption' => 'decimal:3',
            'actual_consumption' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(SupplyContribution::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(SupplyConsumption::class);
    }

    public function isBelowMinimum(): bool
    {
        return (float) $this->current_stock < (float) $this->minimum_stock;
    }
}
