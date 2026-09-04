<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailySupplyPlan extends Model
{
    protected $fillable = [
        'plan_date', 'daily_meal_id', 'people_count',
        'still_water_required', 'mineral_water_required', 'snacks_required', 'desserts_required',
        'still_water_confirmed', 'mineral_water_confirmed', 'snacks_confirmed', 'desserts_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'plan_date' => 'date',
            'still_water_required' => 'decimal:3',
            'mineral_water_required' => 'decimal:3',
            'snacks_required' => 'decimal:3',
            'desserts_required' => 'decimal:3',
            'still_water_confirmed' => 'decimal:3',
            'mineral_water_confirmed' => 'decimal:3',
            'snacks_confirmed' => 'decimal:3',
            'desserts_confirmed' => 'decimal:3',
        ];
    }

    public function dailyMeal(): BelongsTo
    {
        return $this->belongsTo(DailyMeal::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(SupplyConsumption::class);
    }

    public function toBuy(string $resource): float
    {
        return max(0, (float) $this->getAttribute("{$resource}_required") - (float) $this->getAttribute("{$resource}_confirmed"));
    }
}
