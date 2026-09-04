<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_supply_roles_have_expected_permissions(): void
    {
        $manager = new User(['role' => 'supply_manager']);
        $responsible = new User(['role' => 'congregation_responsible']);
        $supervisor = new User(['role' => 'project_supervisor']);

        $this->assertTrue($manager->canManageSupply());
        $this->assertTrue($manager->canManageContributions());
        $this->assertFalse($responsible->canManageSupply());
        $this->assertTrue($responsible->canManageContributions());
        $this->assertFalse($supervisor->canManageSupply());
        $this->assertFalse($supervisor->canManageContributions());
        $this->assertTrue($supervisor->isProjectSupervisor());
    }
}
