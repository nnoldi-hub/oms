<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\DailyMealCostSnapshot;
use App\Models\FoodFundTransaction;
use App\Models\PublicCongregationWeekLink;
use App\Models\User;
use App\Models\Week;
use App\Models\WeekChangeLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WeekCongregationChanger
{
    /**
     * Rezumatul a ceea ce se schimba daca saptamana este mutata la congregatia tinta.
     *
     * @return array{
     *     from: ?string,
     *     to: ?string,
     *     days: array<int, array{date: ?string, congregation: ?string, people: int, cooked: bool}>,
     *     people: int,
     *     volunteers: int,
     *     work_teams: int,
     *     contributions: int,
     *     has_cooked: bool
     * }
     */
    public function preview(Week $week, ?Congregation $target): array
    {
        $meals = $week->dailyMeals()
            ->with(['congregation', 'volunteers', 'workTeams'])
            ->orderBy('meal_date')
            ->get();

        $hasCooked = $meals->contains(fn (DailyMeal $meal): bool => (bool) $meal->is_cooked || $meal->actual_cost !== null);

        return [
            'from' => $week->congregation?->name,
            'to' => $target?->name,
            'days' => $meals->map(fn (DailyMeal $meal): array => [
                'date' => $meal->meal_date?->translatedFormat('d M Y'),
                'congregation' => $meal->congregation?->name,
                'people' => $meal->portions(),
                'cooked' => (bool) $meal->is_cooked || $meal->actual_cost !== null,
            ])->all(),
            'people' => (int) $meals->sum(fn (DailyMeal $meal): int => $meal->portions()),
            'volunteers' => (int) $meals->sum(fn (DailyMeal $meal): int => $meal->volunteers->count()),
            'work_teams' => (int) $meals->sum(fn (DailyMeal $meal): int => $meal->workTeams->count()),
            'contributions' => $this->contributionsFor($meals)->count(),
            'has_cooked' => $hasCooked,
        ];
    }

    public function move(Week $week, Congregation $target, string $reason, User $user): WeekChangeLog
    {
        $fromId = $week->congregation_id;

        return DB::transaction(function () use ($week, $target, $fromId, $reason, $user): WeekChangeLog {
            $meals = $week->dailyMeals()->with(['volunteers', 'workTeams'])->get();

            $week->congregation_id = $target->id;
            $week->save();

            $this->relocateMeals($meals, $target);

            return WeekChangeLog::create([
                'type' => WeekChangeLog::TYPE_MOVE,
                'week_id' => $week->id,
                'second_week_id' => null,
                'from_congregation_id' => $fromId,
                'to_congregation_id' => $target->id,
                'user_id' => $user->id,
                'days_moved' => $meals->count(),
                'reason' => $reason,
            ]);
        });
    }

    public function swap(Week $week, Week $other, string $reason, User $user): WeekChangeLog
    {
        $fromId = $week->congregation_id;
        $otherFromId = $other->congregation_id;

        return DB::transaction(function () use ($week, $other, $fromId, $otherFromId, $reason, $user): WeekChangeLog {
            $meals = $week->dailyMeals()->with(['volunteers', 'workTeams'])->get();
            $otherMeals = $other->dailyMeals()->with(['volunteers', 'workTeams'])->get();

            $week->congregation_id = $otherFromId;
            $week->save();
            $other->congregation_id = $fromId;
            $other->save();

            if ($otherFromId !== null) {
                $this->relocateMeals($meals, Congregation::query()->find($otherFromId));
            }

            if ($fromId !== null) {
                $this->relocateMeals($otherMeals, Congregation::query()->find($fromId));
            }

            return WeekChangeLog::create([
                'type' => WeekChangeLog::TYPE_SWAP,
                'week_id' => $week->id,
                'second_week_id' => $other->id,
                'from_congregation_id' => $fromId,
                'to_congregation_id' => $otherFromId,
                'user_id' => $user->id,
                'days_moved' => $meals->count() + $otherMeals->count(),
                'reason' => $reason,
            ]);
        });
    }

    /**
     * @param  Collection<int, DailyMeal>  $meals
     */
    private function relocateMeals(Collection $meals, ?Congregation $target): void
    {
        $note = sprintf(
            'Congregatia responsabila a fost schimbata la %s la data %s.',
            $target?->name ?? 'alta congregatie',
            now()->translatedFormat('d M Y H:i'),
        );

        $mealIds = $meals->pluck('id');
        $weekIds = $meals->pluck('week_id')->filter()->unique();

        foreach ($meals as $meal) {
            $meal->volunteers()->delete();
            $meal->workTeams()->delete();

            $meal->congregation_id = $target?->id;
            $meal->notes = trim(($meal->notes ? $meal->notes."\n" : '').$note);
            $meal->save();
        }

        DailyMealCostSnapshot::query()
            ->whereIn('daily_meal_id', $mealIds)
            ->update(['congregation_id' => $target?->id]);

        PublicCongregationWeekLink::query()
            ->whereIn('week_id', $weekIds)
            ->update(['congregation_id' => $target?->id]);

        FoodFundTransaction::query()
            ->whereIn('daily_meal_id', $mealIds)
            ->get()
            ->each(function (FoodFundTransaction $transaction) use ($target, $note): void {
                $transaction->congregation_id = $target?->id;
                $transaction->description = trim(($transaction->description ? $transaction->description."\n" : '').'['.$note.']');
                $transaction->save();
            });
    }

    /**
     * @param  Collection<int, DailyMeal>  $meals
     * @return Collection<int, FoodFundTransaction>
     */
    private function contributionsFor(Collection $meals): Collection
    {
        if ($meals->isEmpty()) {
            return collect();
        }

        return FoodFundTransaction::query()
            ->whereIn('daily_meal_id', $meals->pluck('id'))
            ->get();
    }
}
