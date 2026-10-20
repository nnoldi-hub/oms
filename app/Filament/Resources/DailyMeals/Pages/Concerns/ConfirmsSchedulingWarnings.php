<?php

namespace App\Filament\Resources\DailyMeals\Pages\Concerns;

use App\Models\DailyMeal;
use App\Services\MenuSchedulingValidator;
use Filament\Notifications\Notification;

trait ConfirmsSchedulingWarnings
{
    public ?string $acknowledgedSchedulingWarnings = null;

    /**
     * Returneaza false cand exista avertismente de rotatie inca neconfirmate.
     */
    protected function acknowledgesSchedulingWarnings(DailyMeal $candidate): bool
    {
        $validator = app(MenuSchedulingValidator::class);

        if (! $validator->scheduleChanged($candidate)) {
            return true;
        }

        $warnings = $validator->warnings($candidate);

        if ($warnings === []) {
            return true;
        }

        $acknowledgement = md5(json_encode($warnings));

        if ($this->acknowledgedSchedulingWarnings === $acknowledgement) {
            return true;
        }

        $this->acknowledgedSchedulingWarnings = $acknowledgement;

        Notification::make()
            ->warning()
            ->title('Verifica rotatia retetelor')
            ->body(implode(' ', $warnings).' Daca repetarea este intentionata, apasa inca o data butonul de salvare.')
            ->persistent()
            ->send();

        return false;
    }
}
