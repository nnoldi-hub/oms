<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\MealChangeLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Schimbari la nivel de zi: inlocuirea unei congregatii contribuitoare sau
 * interschimbarea a doua congregatii contribuitoare intre doua zile.
 * Congregatia care gateceste (congregation_id) NU se modifica aici; schimbarea
 * ei ramane la nivel de saptamana.
 */
class DayCongregationChanger
{
    public function replace(DailyMeal $meal, Congregation $from, Congregation $to, string $reason, User $user): MealChangeLog
    {
        if (! $meal->hasContributingCongregation((int) $from->id)) {
            throw ValidationException::withMessages([
                'dayChangeFromCongregationId' => 'Congregatia selectionata nu contribuie la ziua aleasa.',
            ]);
        }

        if ($meal->hasCookingCongregation((int) $to->id)) {
            throw ValidationException::withMessages([
                'dayChangeToCongregationId' => 'Congregatia care pregateste masa nu poate fi adaugata si ca contribuitoare.',
            ]);
        }

        return DB::transaction(function () use ($meal, $from, $to, $reason, $user): MealChangeLog {
            $ids = collect($meal->contributingCongregationIds())
                ->map(fn (int $id): int => (int) $id === (int) $from->id ? (int) $to->id : $id)
                ->unique()
                ->values()
                ->all();

            $meal->syncContributingCongregations($ids, $user);

            return $this->log($meal, $user, 'congregation_replaced', $from->name, $to->name, $reason);
        });
    }

    public function swap(
        DailyMeal $first,
        DailyMeal $second,
        Congregation $firstCongregation,
        Congregation $secondCongregation,
        string $reason,
        User $user,
    ): MealChangeLog {
        if (! $first->hasContributingCongregation((int) $firstCongregation->id) || ! $second->hasContributingCongregation((int) $secondCongregation->id)) {
            throw ValidationException::withMessages([
                'dayChangeSecondMealId' => 'Congregatiile alese trebuie sa participe la zilele selectate.',
            ]);
        }

        return DB::transaction(function () use ($first, $second, $firstCongregation, $secondCongregation, $reason, $user): MealChangeLog {
            $this->replace($first, $firstCongregation, $secondCongregation, $reason, $user);
            $this->replace($second, $secondCongregation, $firstCongregation, $reason, $user);

            return $this->log(
                $first,
                $user,
                'congregation_swapped',
                $firstCongregation->name,
                $secondCongregation->name.' (ziua '.$second->meal_date->translatedFormat('d M Y').')',
                $reason,
            );
        });
    }

    private function log(
        DailyMeal $meal,
        User $user,
        string $eventType,
        string $oldValue,
        string $newValue,
        string $reason,
    ): MealChangeLog {
        return MealChangeLog::create([
            'daily_meal_id' => $meal->id,
            'week_id' => $meal->week_id,
            'congregation_id' => $meal->congregation_id,
            'user_id' => $user->id,
            'meal_date' => $meal->meal_date,
            'event_type' => $eventType,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'warnings' => [$reason],
        ]);
    }
}
