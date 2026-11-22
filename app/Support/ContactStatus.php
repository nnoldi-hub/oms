<?php

namespace App\Support;

final class ContactStatus
{
    public const NOT_CONTACTED = 'necontactat';

    public const CONTACTED = 'contactat';

    public const CONFIRMED = 'confirmat';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::NOT_CONTACTED => 'Necontactat',
            self::CONTACTED => 'Contactat',
            self::CONFIRMED => 'Confirmat',
        ];
    }

    public static function label(string $status): string
    {
        return self::options()[$status] ?? 'Necontactat';
    }
}
