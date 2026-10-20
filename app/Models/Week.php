<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Week extends Model
{
    /** @use HasFactory<\Database\Factories\WeekFactory> */
    use HasFactory;

    protected $fillable = ['week_number', 'start_date', 'include_saturday', 'congregation_id'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'include_saturday' => 'boolean'];
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    public function dailyMeals(): HasMany
    {
        return $this->hasMany(DailyMeal::class);
    }
}
