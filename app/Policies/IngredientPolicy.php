<?php

namespace App\Policies;

use App\Models\Ingredient;
use App\Models\User;

class IngredientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isKitchenTeam();
    }

    public function view(User $user, Ingredient $ingredient): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Ingredient $ingredient): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Ingredient $ingredient): bool
    {
        return $this->viewAny($user);
    }
}