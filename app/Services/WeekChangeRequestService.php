<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\User;
use App\Models\Week;
use App\Models\WeekChangeRequest;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class WeekChangeRequestService
{
    public function __construct(private WeekCongregationChanger $changer) {}

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

    public function approve(WeekChangeRequest $request, User $admin, ?string $note = null): WeekChangeRequest
    {
        DB::transaction(function () use ($request, $admin, $note): void {
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

    private function notifyCoordinator(WeekChangeRequest $request, bool $approved, ?string $note): void
    {
        $coordinator = $request->user;

        if ($coordinator === null) {
            return;
        }

        $body = $approved
            ? 'Cererea de schimbare a saptamanii a fost aprobata.'
            : 'Cererea de schimbare a saptamanii a fost respinsa.';

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
