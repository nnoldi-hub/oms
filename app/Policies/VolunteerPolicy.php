<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Volunteer;

class VolunteerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function view(User $user, Volunteer $volunteer): bool
    {
        return $user->isAdmin()
            || ($user->isCoordinator() && $user->congregation_id === $volunteer->dailyMeal->congregation_id);
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