<?php

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

class MenuPolicy
{
    /**
     * Coordonatorii consulta catalogul de retete si pot crea retete noi pentru
     * congregatia lor, dar nu modifica retetele din catalog sau ale altor congregatii.
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
        return $this->manage($user) || $user->isCoordinator();
    }

    public function update(User $user, Menu $menu): bool
    {
        if ($this->manage($user)) {
            return true;
        }

        return $user->isCoordinator() && $menu->isEditableByCoordinator($user);
    }

    public function delete(User $user, Menu $menu): bool
    {
        if ($this->manage($user)) {
            return true;
        }

        return $user->isCoordinator() && $menu->isEditableByCoordinator($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->manage($user);
    }

    /**
     * Verificarea si unirea retetelor propuse de coordonatori ramane la administrator.
     */
    public function review(User $user): bool
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
