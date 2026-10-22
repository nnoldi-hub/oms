<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Week;

class WeekPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator() || $user->isConstructionTeam();
    }

    public function view(User $user, Week $week): bool
    {
        return $user->isAdmin()
            || $user->isConstructionTeam()
            || ($user->isCoordinator() && (
                $user->congregation_id === $week->congregation_id
                || $week->dailyMeals()->where('congregation_id', $user->congregation_id)->exists()
            ));
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Week $week): bool
    {
        return $user->isAdmin();
    }

    /**
     * Descarcarea raportului PDF al planului saptamanii este rezervata
     * administratorului si coordonatorilor congregatiei respective.
     */
    public function downloadPlan(User $user, Week $week): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isCoordinator() && (
            $user->congregation_id === $week->congregation_id
            || $week->dailyMeals()->where('congregation_id', $user->congregation_id)->exists()
        );
    }

    public function delete(User $user, Week $week): bool
    {
        return $user->isAdmin();
    }
}