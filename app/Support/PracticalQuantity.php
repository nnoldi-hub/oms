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

    public static function roundUp(float $quantity, string $unit): float
    {
        $step = self::STEPS[$unit] ?? 0.0;

        if ($step <= 0 || $quantity <= 0) {
            return round($quantity, 3);
        }

        return ceil(round($quantity / $step, 6)) * $step;
    }

    public static function format(float $quantity, string $unit): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.').' '.$unit;
    }
}
