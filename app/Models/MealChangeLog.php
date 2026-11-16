<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealChangeLog extends Model
{
    public const EVENT_DISH_CHANGED = 'dish_changed';

    public const EVENT_COOKED_DIFFERENT = 'cooked_different';

    public const EVENT_RECIPE_CREATED = 'recipe_created';

    public const EVENT_COST_DEVIATION = 'cost_deviation';

    protected $fillable = [
        'daily_meal_id',
        'week_id',
        'congregation_id',
        'user_id',
        'meal_date',
        'event_type',
        'old_value',
        'new_value',
        'warnings',
    ];

    protected function casts(): array
    {
        return [
            'meal_date' => 'date',
            'warnings' => 'array',
        ];
    }

    public function dailyMeal(): BelongsTo
    {
        return $this->belongsTo(DailyMeal::class);
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventLabel(): string
    {
        return match ($this->event_type) {
            self::EVENT_DISH_CHANGED => 'Fel schimbat',
            self::EVENT_COOKED_DIFFERENT => 'Gatit alt fel decat cel planificat',
            self::EVENT_RECIPE_CREATED => 'Reteta noua',
            self::EVENT_COST_DEVIATION => 'Cost real diferit',
            default => $this->event_type,
        };
    }

    public function summary(): string
    {
        return match ($this->event_type) {
            self::EVENT_DISH_CHANGED => sprintf('„%s” schimbat in „%s”', $this->old_value ?? 'fara', $this->new_value ?? 'fara'),
            self::EVENT_COOKED_DIFFERENT => sprintf('s-a gatit „%s” in loc de „%s”', $this->new_value ?? '-', $this->old_value ?? 'fara'),
            self::EVENT_RECIPE_CREATED => sprintf('reteta noua „%s”', $this->new_value ?? '-'),
            self::EVENT_COST_DEVIATION => sprintf('cost real %s RON fata de estimat %s RON', $this->new_value ?? '-', $this->old_value ?? '-'),
            default => (string) ($this->new_value ?? ''),
        };
    }
}
