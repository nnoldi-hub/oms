<?php

namespace Tests\Unit;

use App\Services\SupplyPlanningService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SupplyPlanningServiceTest extends TestCase
{
    public function test_it_calculates_water_snacks_and_desserts_from_people_count(): void
    {
        $requirements = (new SupplyPlanningService())->calculateRequirements(84);

        $this->assertSame(42.0, $requirements['water_liters']);
        $this->assertSame(42.0, $requirements['still_water_liters']);
        $this->assertSame(0.0, $requirements['mineral_water_liters']);
        $this->assertSame(84.0, $requirements['snacks_portions']);
        $this->assertSame(84.0, $requirements['desserts_portions']);
    }

    public function test_it_can_split_water_between_still_and_mineral(): void
    {
        $requirements = (new SupplyPlanningService())->calculateRequirements(20, 0.4);

        $this->assertSame(6.0, $requirements['still_water_liters']);
        $this->assertSame(4.0, $requirements['mineral_water_liters']);
    }

    public function test_it_rejects_invalid_input(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SupplyPlanningService())->calculateRequirements(-1);
    }
}
