<?php

namespace App\Support;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\User;
use App\Models\Week;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Reguli comune de vizibilitate pe congregatii.
 *
 * O zi de masa apartine celor trei congregatii din pivot.Coloana veche
 * daily_meals.congregation_id este pastrata numai ca fallback pentru datele
 * care inca nu au fost migrate.
 */
class CongregationAccess
{
    public static function canSeeMeal(User $user, DailyMeal $meal): bool
    {
        if ($user->isAdmin() || $user->isConstructionTeam()) {
            return true;
        }

        if (! self::hasCongregation($user)) {
            return false;
        }

        return $meal->hasCongregation((int) $user->congregation_id)
            || (int) $meal->congregation_id === (int) $user->congregation_id;
    }

    public static function canSeeWeek(User $user, Week $week): bool
    {
        if ($user->isAdmin() || $user->isConstructionTeam()) {
            return true;
        }

        if (! self::hasCongregation($user)) {
            return false;
        }

        $congregationId = (int) $user->congregation_id;

        return (int) $week->congregation_id === $congregationId
            || $week->dailyMeals()->where(function (Builder $query) use ($congregationId): void {
                $query->where('congregation_id', $congregationId)
                    ->orWhereHas('congregations', fn (Builder $pivot) => $pivot->whereKey($congregationId));
            })->exists();
    }

    public static function scopeMeals(Builder $query, ?User $user): Builder
    {
        if (! self::hasCongregation($user)) {
            return $query;
        }

        $congregationId = (int) $user->congregation_id;

        return $query->where(function (Builder $scoped) use ($congregationId): void {
            $scoped->where('daily_meals.congregation_id', $congregationId)
                ->orWhereHas('congregations', fn (Builder $pivot) => $pivot->whereKey($congregationId));
        });
    }

    public static function scopeMealsRelation(Builder|HasMany $query, ?User $user): Builder|HasMany
    {
        if (! self::hasCongregation($user)) {
            return $query;
        }

        $congregationId = (int) $user->congregation_id;

        $query->where(function (Builder $scoped) use ($congregationId): void {
            $scoped->where('daily_meals.congregation_id', $congregationId)
                ->orWhereHas('congregations', fn (Builder $pivot) => $pivot->whereKey($congregationId));
        });

        return $query;
    }

    public static function scopeWeeks(Builder $query, ?User $user): Builder
    {
        if (! self::hasCongregation($user)) {
            return $query;
        }

        $congregationId = (int) $user->congregation_id;

        return $query->where(function (Builder $scoped) use ($congregationId): void {
            $scoped->where('weeks.congregation_id', $congregationId)
                ->orWhereHas('dailyMeals', fn (Builder $meals) => $meals->where(function (Builder $meal) use ($congregationId): void {
                    $meal->where('daily_meals.congregation_id', $congregationId)
                        ->orWhereHas('congregations', fn (Builder $pivot) => $pivot->whereKey($congregationId));
                }));
        });
    }

    public static function canSeeCongregation(User $user, Congregation $congregation): bool
    {
        return $user->isAdmin()
            || (self::hasCongregation($user) && (int) $user->congregation_id === (int) $congregation->id);
    }

    private static function hasCongregation(?User $user): bool
    {
        return $user !== null && $user->congregation_id !== null;
    }
}
