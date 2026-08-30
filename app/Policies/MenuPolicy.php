<?php

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isKitchenTeam();
    }

    public function view(User $user, Menu $menu): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Menu $menu): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Menu $menu): bool
    {
        return $this->viewAny($user);
    }
}