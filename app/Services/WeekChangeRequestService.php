<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\User;
use App\Models\Week;
use App\Models\WeekChangeRequest;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class WeekChangeRequestService
{
    public function __construct(
        private WeekCongregationChanger $changer,
        private DayCongregationChanger $dayChanger,
    ) {}

    public function create(
        Week $week,
        User $user,
        string $reason,
        ?Congregation $preferredCongregation = null,
        ?Week $preferredWeek = null,
    ): WeekChangeRequest {
        $request = WeekChangeRequest::create([
            'week_id' => $week->id,
            'congregation_id' => $week->congregation_id ?? $user->congregation_id,
            'user_id' => $user->id,
            'reason' => $reason,
            'preferred_week_id' => $preferredWeek?->id,
            'preferred_congregation_id' => $preferredCongregation?->id,
            'status' => WeekChangeRequest::STATUS_PENDING,
        ]);

        $admins = User::query()->where('role', 'admin')->get();

        if ($admins->isNotEmpty()) {
            $body = sprintf(
                '%s (%s) cere schimbarea saptamanii %s. Motiv: %s',
                $user->name,
                $week->congregation?->name ?? 'fara congregatie',
                $week->week_number,
                $reason,
            );

            Notification::make()
                ->title('Cerere de schimbare a saptamanii')
                ->body($body)
                ->warning()
                ->sendToDatabase($admins, true);
        }

        return $request;
    }

    /**
     * Cerere pe zi: inlocuirea unei congregatii sau interschimbarea a doua zile.
     */
    public function createDayRequest(
        DailyMeal $meal,
        User $user,
        string $reason,
        Congregation $from,
        ?Congregation $to = null,
        ?DailyMeal $targetMeal = null,
        ?Congregation $targetFrom = null,
    ): WeekChangeRequest {
        $isSwap = $targetMeal !== null && $targetFrom !== null && $to === null;

        $request = WeekChangeRequest::create([
            'week_id' => $meal->week_id,
            'daily_meal_id' => $meal->id,
            'target_daily_meal_id' => $isSwap ? $targetMeal?->id : null,
            'congregation_id' => $user->congregation_id,
            'from_congregation_id' => $from->id,
            'user_id' => $user->id,
            'reason' => $reason,
            'change_type' => $isSwap ? 'swap' : 'replace',
            'preferred_congregation_id' => $isSwap ? null : $to?->id,
            'status' => WeekChangeRequest::STATUS_PENDING,
        ]);

        $admins = User::query()->where('role', 'admin')->get();

        if ($admins->isNotEmpty()) {
            $body = sprintf(
                '%s cere %s la data %s. Motiv: %s',
                $user->name,
                $isSwap ? 'interschimbarea congregatiilor' : 'schimbarea congregatiei',
                $meal->meal_date->translatedFormat('d M Y'),
                $reason,
            );

            Notification::make()
                ->title('Cerere de schimbare a zilei')
                ->body($body)
                ->warning()
                ->sendToDatabase($admins, true);
        }

        return $request;
    }

    public function approve(WeekChangeRequest $request, User $admin, ?string $note = null): WeekChangeRequest
    {
        DB::transaction(function () use ($request, $admin, $note): void {
            if ($request->daily_meal_id !== null) {
                $this->approveDayChange($request, $admin);

                $request->status = WeekChangeRequest::STATUS_APPROVED;
                $request->resolved_by = $admin->id;
                $request->resolved_at = now();
                $request->resolution_note = $note;
                $request->save();

                return;
            }

            $week = $request->week;

            if ($week !== null) {
                if ($request->preferred_week_id !== null) {
                    $other = Week::query()->find($request->preferred_week_id);

                    if ($other !== null) {
                        $this->changer->swap($week, $other, $request->reason, $admin);
                    }
                } elseif ($request->preferred_congregation_id !== null) {
                    $target = Congregation::query()->find($request->preferred_congregation_id);

                    if ($target !== null) {
                        $this->changer->move($week, $target, $request->reason, $admin);
                    }
                }
            }

            $request->status = WeekChangeRequest::STATUS_APPROVED;
            $request->resolved_by = $admin->id;
            $request->resolved_at = now();
            $request->resolution_note = $note;
            $request->save();
        });

        $this->notifyCoordinator($request, true, $note);

        return $request;
    }

    public function reject(WeekChangeRequest $request, User $admin, ?string $note = null): WeekChangeRequest
    {
        $request->status = WeekChangeRequest::STATUS_REJECTED;
        $request->resolved_by = $admin->id;
        $request->resolved_at = now();
        $request->resolution_note = $note;
        $request->save();

        $this->notifyCoordinator($request, false, $note);

        return $request;
    }

    private function approveDayChange(WeekChangeRequest $request, User $admin): void
    {
        $meal = $request->dailyMeal;
        $from = $request->fromCongregation;

        if ($meal === null || $from === null) {
            return;
        }

        if ($request->change_type === 'swap') {
            $targetMeal = $request->targetDailyMeal;
            $targetFrom = $request->congregation;

            if ($targetMeal !== null && $targetFrom !== null) {
                $this->dayChanger->swap($meal, $targetMeal, $from, $targetFrom, $request->reason, $admin);
            }

            return;
        }

        $to = $request->preferredCongregation;

        if ($to !== null) {
            $this->dayChanger->replace($meal, $from, $to, $request->reason, $admin);
        }
    }

    private function notifyCoordinator(WeekChangeRequest $request, bool $approved, ?string $note): void
    {
        $coordinator = $request->user;

        if ($coordinator === null) {
            return;
        }

        $subject = $request->daily_meal_id !== null ? 'zilei' : 'saptamanii';
        $body = $approved
            ? 'Cererea de schimbare a '.$subject.' a fost aprobata.'
            : 'Cererea de schimbare a '.$subject.' a fost respinsa.';

        if (filled($note)) {
            $body .= ' Nota administratorului: '.$note;
        }

        $notification = Notification::make()
            ->title($approved ? 'Cerere aprobata' : 'Cerere respinsa')
            ->body($body);

        if ($approved) {
            $notification->success();
        } else {
            $notification->danger();
        }

        $notification->sendToDatabase($coordinator, true);
    }
}
