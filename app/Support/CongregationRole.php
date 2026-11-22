<?php

namespace App\Support;

/**
 * Rolul unei congregatii in program: pregateste masa sau contribuie cu alimente.
 * Nu contin nume de congregatii — acelea se seteaza prin comanda
 * oms:set-cooking-congregations.
 */
final class CongregationRole
{
    public const COOKS = 'gateste';

    public const CONTRIBUTES = 'contribuie';

    /** @return list<string> */
    public static function options(): array
    {
        return [self::COOKS, self::CONTRIBUTES];
    }
}
