<?php

namespace App\Policies;

use App\Models\Congregation;
use App\Models\User;

class CongregationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator();
    }

    public function view(User $user, Congregation $congregation): bool
    {
        return $user->isAdmin() || ($user->isCoordinator() && $user->congregation_id === $congregation->id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Congregation $congregation): bool
    {
        return $user->isAdmin() || ($user->isCoordinator() && $user->congregation_id === $congregation->id);
    }

    public function delete(User $user, Congregation $congregation): bool
    {
        return $user->isAdmin();
    }
}