<?php

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

class MenuPolicy
{
    /**
     * Coordonatorii consulta catalogul de retete (il folosesc in calendar), dar nu il modifica:
     * catalogul este comun tuturor congregatiilor.
     */
    public function viewAny(User $user): bool
    {
        return $this->manage($user) || $user->isCoordinator();
    }

    public function view(User $user, Menu $menu): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Menu $menu): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Menu $menu): bool
    {
        return $this->manage($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->manage($user);
    }

    /**
     * Raportul complet de retete si costuri ramane la administrator si bucatarie.
     */
    public function viewCostReport(User $user): bool
    {
        return $this->manage($user);
    }

    private function manage(User $user): bool
    {
        return $user->isAdmin() || $user->isKitchenTeam();
    }
}