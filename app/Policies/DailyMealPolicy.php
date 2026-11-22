<?php

namespace App\Policies;

use App\Models\DailyMeal;
use App\Models\User;
use App\Support\CongregationAccess;

class DailyMealPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator() || $user->isConstructionTeam();
    }

    public function view(User $user, DailyMeal $dailyMeal): bool
    {
        return CongregationAccess::canSeeMeal($user, $dailyMeal);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, DailyMeal $dailyMeal): bool
    {
        return $user->isAdmin()
            || $user->isConstructionTeam()
            || ($user->isCoordinator() && CongregationAccess::canSeeMeal($user, $dailyMeal));
    }

    public function delete(User $user, DailyMeal $dailyMeal): bool
    {
        return $user->isAdmin();
    }
}
