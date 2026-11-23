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
 * Congregatia care gatesc este in daily_meals.congregation_id (si
 * weeks.congregation_id). Congregatiile din pivot aduc alimente si au acces
 * doar la programul zilelor in care contribuie, nu la date financiare.
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

        return $meal->hasCookingCongregation((int) $user->congregation_id)
            || $meal->hasContributingCongregation((int) $user->congregation_id);
    }

    public static function canManageMeal(User $user, DailyMeal $meal): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! self::hasCongregation($user)) {
            return false;
        }

        return $meal->hasCookingCongregation((int) $user->congregation_id);
    }

    public static function isContributorOnly(User $user, DailyMeal $meal): bool
    {
        return self::hasCongregation($user)
            && ! $meal->hasCookingCongregation((int) $user->congregation_id)
            && $meal->hasContributingCongregation((int) $user->congregation_id);
    }

    /**
     * Un coordonator care nu gateste nicio saptamana are doar rol de contribuitor:
     * nu vede date financiare, nici rapoartele complete.
     */
    public static function isContributorOnlyUser(User $user): bool
    {
        if (! $user->isCoordinator() || ! self::hasCongregation($user)) {
            return false;
        }

        return ! self::cooksAnywhere($user);
    }

    /**
     * Un coordonator pregateste masa dacă apare ca congregație care gătește
     * intr-o saptamana sau intr-o zi, chiar si in saptamana speciala de final.
     */
    public static function cooksAnywhere(User $user): bool
    {
        if (! self::hasCongregation($user)) {
            return false;
        }

        $congregationId = (int) $user->congregation_id;

        return Week::query()->where('congregation_id', $congregationId)->exists()
            || DailyMeal::query()->where('congregation_id', $congregationId)->exists();
    }

    /**
     * Detaliile complete ale zilei (meniu, oameni, costuri, lista de cumparaturi)
     * apartin congregatiei care gatesc. Contribuitorii vad doar programul zilei.
     */
    public static function canViewFullMeal(User $user, DailyMeal $meal): bool
    {
        return $user->isAdmin() || self::canManageMeal($user, $meal);
    }

    /**
     * Congregatia care gatesc saptamana poate deschide rapoartele complete.
     */
    public static function cooksInWeek(User $user, Week $week): bool
    {
        if (! self::hasCongregation($user)) {
            return false;
        }

        $congregationId = (int) $user->congregation_id;

        if ((int) $week->congregation_id === $congregationId) {
            return true;
        }

        return $week->dailyMeals()->where('daily_meals.congregation_id', $congregationId)->exists();
    }

    public static function canViewFullWeek(User $user, Week $week): bool
    {
        return $user->isAdmin()
            || ($user->isCoordinator() && self::cooksInWeek($user, $week));
    }

    /**
     * Un coordonator contribuitor poate cere doar inlocuirea propriei participari.
     */
    public static function canChangeContribution(User $user, DailyMeal $meal): bool
    {
        return $user->isAdmin()
            || ($user->isCoordinator() && self::isContributorOnly($user, $meal));
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
                    ->orWhereHas('contributingCongregations', fn (Builder $pivot) => $pivot->whereKey($congregationId));
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
                ->orWhereHas('contributingCongregations', fn (Builder $pivot) => $pivot->whereKey($congregationId));
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
                ->orWhereHas('contributingCongregations', fn (Builder $pivot) => $pivot->whereKey($congregationId));
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
                        ->orWhereHas('contributingCongregations', fn (Builder $pivot) => $pivot->whereKey($congregationId));
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
