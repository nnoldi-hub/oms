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

    public function delete(User $user, Week $week): bool
    {
        return $user->isAdmin();
    }
}