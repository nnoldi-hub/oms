<?php

namespace App\Models;

use App\Services\MenuSchedulingValidator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DailyMeal extends Model
{
    /** @use HasFactory<\Database\Factories\DailyMealFactory> */
    use HasFactory;

    protected $fillable = [
        'meal_date',
        'week_id',
        'congregation_id',
        'menu_id',
        'soup_menu_id',
        'dessert_menu_id',
        'estimated_people',
        'maximum_budget',
        'contributor_count',
        'notes',
        'public_token',
        'status',
    ];

    protected function casts(): array
    {
        return ['meal_date' => 'date', 'maximum_budget' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::creating(function (DailyMeal $dailyMeal): void {
            $dailyMeal->public_token ??= (string) Str::uuid();
        });

        static::saving(function (DailyMeal $dailyMeal): void {
            $user = auth()->user();

            if ($dailyMeal->exists && $user?->isConstructionTeam() && $dailyMeal->isDirty([
                'meal_date',
                'week_id',
                'menu_id',
                'soup_menu_id',
                'dessert_menu_id',
                'notes',
                'maximum_budget',
                'contributor_count',
                'public_token',
                'status',
            ])) {
                throw new AuthorizationException('Echipa de constructii poate modifica doar numarul de persoane.');
            }

            if ($dailyMeal->exists && $user?->isCoordinator()) {
                if ($user->congregation_id !== $dailyMeal->congregation_id || $dailyMeal->isDirty([
                    'meal_date',
                    'week_id',
                    'congregation_id',
                    'estimated_people',
                    'notes',
                    'public_token',
                    'status',
                ])) {
                    throw new AuthorizationException('Coordonatorul poate modifica doar retetele zilelor congregatiei sale.');
                }
            }

            app(MenuSchedulingValidator::class)->validate($dailyMeal);
        });
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function soupMenu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'soup_menu_id');
    }

    public function dessertMenu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'dessert_menu_id');
    }

    public function volunteers(): HasMany
    {
        return $this->hasMany(Volunteer::class);
    }
}
