<?php

namespace App\Support;

class CombinedIngredients
{
    /**
     * Ingrediente combinate folosite in retete, impartite in componentele lor.
     *
     * @var array<string, list<string>>
     */
    public const SPLITS = [
        'Ceapa si morcovi' => ['Ceapa', 'Morcovi'],
        'Ceapa si boia' => ['Ceapa', 'Boia'],
        'Lapte si unt' => ['Lapte', 'Unt'],
        'Sos de rosii si usturoi' => ['Sos de rosii', 'Usturoi'],
        'Faina si oua' => ['Faina', 'Oua'],
        'Zahar si ulei' => ['Zahar', 'Ulei'],
        'Faina si cacao' => ['Faina', 'Cacao'],
    ];

    /** @return list<string>|null */
    public static function partsFor(string $name): ?array
    {
        $name = trim($name);

        foreach (self::SPLITS as $combined => $parts) {
            if (strcasecmp($combined, $name) === 0) {
                return $parts;
            }
        }

        return null;
    }
}
