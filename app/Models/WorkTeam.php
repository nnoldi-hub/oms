<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkTeam extends Model
{
    protected $fillable = [
        'daily_meal_id', 'name', 'member_count', 'starts_at', 'ends_at',
        'location', 'water_required', 'snacks_required', 'supply_responsible',
    ];

    protected function casts(): array
    {
        return [
            'water_required' => 'decimal:3',
            'snacks_required' => 'decimal:3',
        ];
    }

    public function dailyMeal(): BelongsTo
    {
        return $this->belongsTo(DailyMeal::class);
    }
}
