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

    /**
     * Imparte un ingredient combinat in componentele lui.
     *
     * Cantitatea amestecului nu se imparte in mod egal, iar pretul amestecului nu se
     * copiaza pe componente: fiecare componenta ramane "de configurat", cu cantitatea
     * si pretul de stabilit manual de catre bucatar.
     *
     * @param  array<string, mixed>  $ingredient
     * @return list<array<string, mixed>>|null
     */
    public static function splitFor(array $ingredient): ?array
    {
        $parts = self::partsFor((string) ($ingredient['name'] ?? ''));

        if ($parts === null) {
            return null;
        }

        $unit = (string) ($ingredient['unit'] ?? 'kg');

        return array_map(fn (string $part): array => [
            'name' => $part,
            'quantity_per_person' => 0,
            'unit' => $unit,
            'needs_configuration' => true,
        ], $parts);
    }
}
