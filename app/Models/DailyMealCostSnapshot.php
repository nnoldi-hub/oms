<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyMealCostSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_meal_id',
        'congregation_id',
        'meal_date',
        'portions',
        'ingredients_cost',
        'fixed_costs_cost',
        'packaging_cost',
        'total_cost',
        'cost_per_portion',
        'has_missing_prices',
    ];

    protected function casts(): array
    {
        return [
            'meal_date' => 'date',
            'portions' => 'integer',
            'ingredients_cost' => 'decimal:2',
            'fixed_costs_cost' => 'decimal:2',
            'packaging_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'cost_per_portion' => 'decimal:2',
            'has_missing_prices' => 'boolean',
        ];
    }

    public function dailyMeal(): BelongsTo
    {
        return $this->belongsTo(DailyMeal::class);
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    /**
     * @return array{total_cost: float, has_missing_prices: bool, is_partial: bool}
     */
    public function toCostArray(): array
    {
        return [
            'total_cost' => (float) $this->total_cost,
            'has_missing_prices' => (bool) $this->has_missing_prices,
            'is_partial' => (bool) $this->has_missing_prices,
        ];
    }
}
