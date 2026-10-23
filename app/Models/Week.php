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

    /**
     * Perioada raportului: de la prima la ultima masa programata, nu saptamana calendaristica.
     */
    public function periodLabel(): string
    {
        $meals = $this->dailyMeals
            ->filter(fn (DailyMeal $meal): bool => $meal->meal_date !== null)
            ->sortBy(fn (DailyMeal $meal): int => $meal->meal_date->getTimestamp());

        $start = $meals->first()?->meal_date ?? $this->start_date;
        $end = $meals->last()?->meal_date ?? $start;

        return $start->translatedFormat('j F Y').' – '.$end->translatedFormat('j F Y');
    }
}
