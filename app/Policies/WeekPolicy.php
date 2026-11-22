<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Week;
use App\Support\CongregationAccess;

class WeekPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCoordinator() || $user->isConstructionTeam();
    }

    public function view(User $user, Week $week): bool
    {
        return CongregationAccess::canSeeWeek($user, $week);
    }

    public function viewFull(User $user, Week $week): bool
    {
        return CongregationAccess::canViewFullWeek($user, $week);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Week $week): bool
    {
        return $user->isAdmin();
    }

    /**
     * Descarcarea raportului PDF al planului saptamanii este rezervata
     * administratorului si coordonatorilor congregatiilor prezente in saptamana.
     */
    public function downloadPlan(User $user, Week $week): bool
    {
        return CongregationAccess::canViewFullWeek($user, $week);
    }

    public function delete(User $user, Week $week): bool
    {
        return $user->isAdmin();
    }
}
