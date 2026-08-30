<?php

namespace App\Policies;

use App\Models\DailyMeal;
use App\Models\User;

class DailyMealPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator() || $user->isConstructionTeam();
    }

    public function view(User $user, DailyMeal $dailyMeal): bool
    {
        return $user->isAdmin()
            || $user->isConstructionTeam()
            || ($user->isCoordinator() && $user->congregation_id === $dailyMeal->congregation_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, DailyMeal $dailyMeal): bool
    {
        return $user->isAdmin()
            || $user->isConstructionTeam()
            || ($user->isCoordinator() && $user->congregation_id === $dailyMeal->congregation_id);
    }

    public function delete(User $user, DailyMeal $dailyMeal): bool
    {
        return $user->isAdmin();
    }
}