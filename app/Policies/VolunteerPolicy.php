<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Volunteer;
use App\Support\CongregationAccess;

class VolunteerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function view(User $user, Volunteer $volunteer): bool
    {
        return $user->isAdmin()
            || ($user->isCoordinator() && CongregationAccess::canSeeMeal($user, $volunteer->dailyMeal));
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function update(User $user, Volunteer $volunteer): bool
    {
        return $this->view($user, $volunteer);
    }

    public function delete(User $user, Volunteer $volunteer): bool
    {
        return $this->view($user, $volunteer);
    }
}
