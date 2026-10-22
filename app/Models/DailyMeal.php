<?php

namespace App\Models;

use App\Services\MenuSchedulingValidator;
use Database\Factories\DailyMealFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class DailyMeal extends Model
{
    /** @use HasFactory<DailyMealFactory> */
    use HasFactory;

    protected $fillable = [
        'meal_date',
        'week_id',
        'congregation_id',
        'menu_id',
        'soup_menu_id',
        'dessert_menu_id',
        'estimated_people',
        'confirmed_people',
        'maximum_budget',
        'contributor_count',
        'notes',
        'delivery_time',
        'public_token',
        'status',
        'is_free_day',
        'is_cooked',
        'actual_cost',
        'actual_menu_id',
    ];

    protected function casts(): array
    {
        return [
            'meal_date' => 'date',
            'estimated_people' => 'integer',
            'confirmed_people' => 'integer',
            'maximum_budget' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'is_free_day' => 'boolean',
            'is_cooked' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DailyMeal $dailyMeal): void {
            $dailyMeal->public_token ??= (string) Str::uuid();
        });

        static::saving(function (DailyMeal $dailyMeal): void {
            $user = auth()->user();

            // ⚠️ COREMPARE: Verificăm restricțiile de securitate doar dacă înregistrarea EXISTĂ deja în baza de date
            // Acest lucru lasă generatorul automat să creeze zilele goale fără să verifice rolurile utilizatorilor
            if ($dailyMeal->exists) {

                // 1. Restricții pentru Echipa de Construcții
                if ($user?->isConstructionTeam() && $dailyMeal->isDirty([
                    'meal_date',
                    'week_id',
                    'congregation_id',
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

                // 2. Restricții pentru Coordonatorul de Congregație
                if ($user?->isCoordinator()) {
                    if ($user->congregation_id !== $dailyMeal->congregation_id || $dailyMeal->isDirty([
                        'meal_date',
                        'week_id',
                        'congregation_id',
                        'estimated_people',
                        'confirmed_people',
                        'notes',
                        'delivery_time',
                        'public_token',
                        'status',
                    ])) {
                        throw new AuthorizationException('Coordonatorul poate modifica doar retetele zilelor congregatiei sale.');
                    }
                }
            }

            $validator = app(MenuSchedulingValidator::class);

            if ($validator->scheduleChanged($dailyMeal)) {
                $validator->validate($dailyMeal);
            }
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

    public function actualMenu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'actual_menu_id');
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

    public function supplyPlan(): HasOne
    {
        return $this->hasOne(DailySupplyPlan::class);
    }

    public function workTeams(): HasMany
    {
        return $this->hasMany(WorkTeam::class);
    }

    /**
     * Numarul de portii pe care se face calculul: confirmat daca exista, altfel estimat.
     */
    public function portions(): int
    {
        return max(0, (int) ($this->confirmed_people ?? $this->estimated_people));
    }
}
