<?php

namespace Tests\Unit;

use App\Support\PracticalQuantity;
use PHPUnit\Framework\TestCase;

class PracticalQuantityTest extends TestCase
{
    public function test_it_rounds_quantities_up_to_practical_shopping_steps(): void
    {
        $this->assertSame(4.5, PracticalQuantity::roundUp(4.2, 'kg'));
        $this->assertSame(4.0, PracticalQuantity::roundUp(4, 'kg'));
        $this->assertSame(0.5, PracticalQuantity::roundUp(0.1, 'kg'));
        $this->assertSame(0.0, PracticalQuantity::roundUp(0, 'kg'));
        $this->assertSame(2.5, PracticalQuantity::roundUp(2.3, 'l'));
        $this->assertSame(1.5, PracticalQuantity::roundUp(1.5, 'l'));
        $this->assertSame(2.0, PracticalQuantity::roundUp(1.5, 'buc'));
        $this->assertSame(3.0, PracticalQuantity::roundUp(3, 'buc'));
        $this->assertSame(100.0, PracticalQuantity::roundUp(1, 'g'));
    }

    public function test_it_leaves_unknown_units_untouched(): void
    {
        $this->assertSame(1.234, PracticalQuantity::roundUp(1.234, 'set'));
    }

    public function test_it_formats_the_quantity_without_trailing_zeros(): void
    {
        $this->assertSame('4.5 kg', PracticalQuantity::format(4.5, 'kg'));
        $this->assertSame('2 buc', PracticalQuantity::format(2, 'buc'));
        $this->assertSame('0.5 l', PracticalQuantity::format(0.5, 'l'));
    }
}
