<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\FoodFundTransaction;
use App\Models\MealChangeLog;
use App\Models\PublicCongregationWeekLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Schimbari la nivel de zi: inlocuirea unei congregatii din cele trei sau
 * interschimbarea a doua congregatii intre doua zile.
 */
class DayCongregationChanger
{
    public function replace(DailyMeal $meal, Congregation $from, Congregation $to, string $reason, User $user): MealChangeLog
    {
        if (! $meal->hasCongregation((int) $from->id)) {
            throw ValidationException::withMessages([
                'dayChangeFromCongregationId' => 'Congregatia selectionata nu participa la ziua aleasa.',
            ]);
        }

        return DB::transaction(function () use ($meal, $from, $to, $reason, $user): MealChangeLog {
            $ids = collect($meal->congregationIds())
                ->map(fn (int $id): int => (int) $id === (int) $from->id ? (int) $to->id : $id)
                ->unique()
                ->values()
                ->all();

            $meal->syncCongregations($ids, $user);

            if ((int) $meal->congregation_id === (int) $from->id) {
                $meal->congregation_id = (int) $to->id;
                $meal->saveQuietly();
            }

            $this->relinkFinancialData($meal, $from, $to, $reason);

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
        if (! $first->hasCongregation((int) $firstCongregation->id) || ! $second->hasCongregation((int) $secondCongregation->id)) {
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

    private function relinkFinancialData(DailyMeal $meal, Congregation $from, Congregation $to, string $reason): void
    {
        if ((int) $meal->congregation_id === (int) $from->id) {
            PublicCongregationWeekLink::query()
                ->where('week_id', $meal->week_id)
                ->where('congregation_id', $from->id)
                ->update(['congregation_id' => $to->id]);
        }

        FoodFundTransaction::query()
            ->where('daily_meal_id', $meal->id)
            ->where('congregation_id', $from->id)
            ->get()
            ->each(function (FoodFundTransaction $transaction) use ($to, $reason): void {
                $transaction->congregation_id = $to->id;
                $transaction->description = trim(($transaction->description ? $transaction->description."\n" : '').'['.$reason.']');
                $transaction->save();
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
