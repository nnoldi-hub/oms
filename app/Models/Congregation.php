<?php

namespace App\Models;

use Database\Factories\CongregationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Congregation extends Model
{
    /** @use HasFactory<CongregationFactory> */
    use HasFactory;

    protected $fillable = ['name', 'assistant_name', 'assistant_phone', 'assistant_email'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(Week::class);
    }

    /**
     * @deprecated O zi are trei congregatii; foloseste scheduledDailyMeals().
     */
    public function dailyMeals(): HasMany
    {
        return $this->hasMany(DailyMeal::class);
    }

    /**
     * Toate zilele in care congregatia este una dintre cele trei programate.
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
