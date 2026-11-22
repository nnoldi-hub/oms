<?php

namespace App\Models;

use App\Support\CongregationRole;
use Database\Factories\CongregationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Congregation extends Model
{
    /** @use HasFactory<CongregationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'support_notes',
        'assistant_name',
        'assistant_phone',
        'assistant_email',
    ];

    public function isCooking(): bool
    {
        return $this->type === CongregationRole::COOKS;
    }

    public function isContributing(): bool
    {
        return $this->type === CongregationRole::CONTRIBUTES;
    }

    public function scopeCooking(Builder $query): Builder
    {
        return $query->where('type', CongregationRole::COOKS);
    }

    public function scopeContributing(Builder $query): Builder
    {
        return $query->where('type', CongregationRole::CONTRIBUTES);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(Week::class);
    }

    /**
     * Zilele pe care aceasta congregatie le pregateste (congregatia saptamanii).
     */
    public function dailyMeals(): HasMany
    {
        return $this->hasMany(DailyMeal::class);
    }

    /**
     * Zilele in care congregatia gatesc sau contribuie (uz in fisele publice).
     */
    public function scheduledDailyMeals(): BelongsToMany
    {
        $congregationId = (int) $this->getKey();

        return $this->belongsToMany(DailyMeal::class, 'congregation_daily_meal')
            ->withPivot('position')
            ->withTimestamps()
            ->where(function ($query) use ($congregationId): void {
                $query->where('congregation_daily_meal.congregation_id', $congregationId)
                    ->orWhere('daily_meals.congregation_id', $congregationId);
            })
            ->orderBy('daily_meals.meal_date');
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class)->withTimestamps();
    }

    public function supplyContributions(): HasMany
    {
        return $this->hasMany(SupplyContribution::class);
    }

    /**
     * Zilele in care congregatia aduce gustari, fructe, cafea sau ceai.
     */
    public function contributingDailyMeals(): BelongsToMany
    {
        return $this->belongsToMany(DailyMeal::class, 'congregation_daily_meal')
            ->withPivot(['position', 'contact_status', 'contacted_at', 'contact_notes'])
            ->withTimestamps();
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function donorDonations(): HasMany
    {
        return $this->hasMany(Donation::class, 'donor_congregation_id');
    }

    protected static function booted(): void
    {
        static::saving(function (Congregation $congregation): void {
            $user = auth()->user();

            if ($congregation->exists && $user?->isCoordinator() && $congregation->isDirty('name')) {
                throw ValidationException::withMessages([
                    'name' => 'Coordonatorul poate modifica doar retetele aprobate.',
                ]);
            }
        });
    }
}
