<?php

namespace App\Support;

class PracticalQuantity
{
    private const STEPS = [
        'buc' => 1.0,
        'l' => 0.5,
        'kg' => 0.5,
        'g' => 100.0,
    ];

    /**
     * Rotunjirea cantitatii de cumparat.
     *
     * Pentru kg, cantitatile mici (sub 1 kg) se rotunjesc la 0,1 kg, iar cele mari
     * la 0,5 kg. Daca ingredientul are un pachet configurat, se cumpara in pachete
     * intregi de acea cantitate.
     */
    public static function roundUp(float $quantity, string $unit, ?float $packageQuantity = null): float
    {
        $step = $packageQuantity !== null && $packageQuantity > 0
            ? $packageQuantity
            : self::stepFor($quantity, $unit);

        if ($step <= 0 || $quantity <= 0) {
            return round($quantity, 3);
        }

        return round(ceil(round($quantity / $step, 6)) * $step, 3);
    }

    public static function format(float $quantity, string $unit): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.').' '.$unit;
    }

    private static function stepFor(float $quantity, string $unit): float
    {
        if ($unit === 'kg') {
            return $quantity < 1.0 ? 0.1 : 0.5;
        }

        return self::STEPS[$unit] ?? 0.0;
    }
}
