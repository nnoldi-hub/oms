<?php

namespace App\Services;

use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Muta toate retetele de pe un ingredient duplicat pe ingredientul pastrat.
 *
 * Retetele tin ingredientele intr-o coloana JSON (menus.ingredients), cu linii de forma:
 * {"name":"Cartofi","quantity_per_person":0.3,"unit":"kg","ingredient_id":18}
 *
 * Se scrie direct in tabela (fara evenimentele modelului Menu), ca sa nu se declanseze
 * validarile sau crearea automata de ingrediente din Menu.php.
 */
class IngredientMerger
{
    /**
     * @return array{menus: int, lines: int, combined: int}
     */
    public function merge(Ingredient $duplicate, Ingredient $keep, bool $dryRun = false): array
    {
        if ($duplicate->is($keep)) {
            throw new InvalidArgumentException('Ingredientul duplicat si cel pastrat sunt acelasi.');
        }

        if ($duplicate->unit !== $keep->unit) {
            throw new InvalidArgumentException(
                "Unitatile difera ({$duplicate->unit} vs {$keep->unit}). Corecteaza cantitatile manual in retete."
            );
        }

        $stats = ['menus' => 0, 'lines' => 0, 'combined' => 0];

        DB::transaction(function () use ($duplicate, $keep, $dryRun, &$stats): void {
            DB::table('menus')
                ->select('id', 'ingredients')
                ->whereNotNull('ingredients')
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($duplicate, $keep, $dryRun, &$stats): void {
                    foreach ($rows as $row) {
                        $lines = json_decode((string) $row->ingredients, true);

                        if (! is_array($lines)) {
                            continue;
                        }

                        $remapped = 0;

                        foreach ($lines as &$line) {
                            if ((int) ($line['ingredient_id'] ?? 0) === (int) $duplicate->id) {
                                $line['ingredient_id'] = $keep->id;
                                $line['name'] = $keep->name;
                                $line['unit'] = $keep->unit;
                                $remapped++;
                            }
                        }
                        unset($line);

                        if ($remapped === 0) {
                            continue;
                        }

                        [$lines, $combined] = $this->combineSameIngredient($lines);

                        $stats['menus']++;
                        $stats['lines'] += $remapped;
                        $stats['combined'] += $combined;

                        if (! $dryRun) {
                            DB::table('menus')->where('id', $row->id)->update([
                                'ingredients' => json_encode($lines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                });

            if (! $dryRun) {
                // Nu stergem duplicatul: il dezactivam, ca sa ramana istoricul si sa se poata reveni.
                $duplicate->update(['is_active' => false]);
            }
        });

        return $stats;
    }

    /**
     * Daca dupa mutare o reteta are acelasi ingredient de doua ori, liniile se aduna.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function combineSameIngredient(array $lines): array
    {
        $result = [];
        $positions = [];
        $combined = 0;

        foreach ($lines as $line) {
            $id = (int) ($line['ingredient_id'] ?? 0);

            if ($id === 0) {
                $result[] = $line;

                continue;
            }

            if (isset($positions[$id])) {
                $index = $positions[$id];
                $result[$index]['quantity_per_person'] = round(
                    (float) ($result[$index]['quantity_per_person'] ?? 0) + (float) ($line['quantity_per_person'] ?? 0),
                    4
                );
                $result[$index]['needs_configuration'] = ! empty($result[$index]['needs_configuration'])
                    || ! empty($line['needs_configuration']);
                $combined++;

                continue;
            }

            $positions[$id] = count($result);
            $result[] = $line;
        }

        return [$result, $combined];
    }
}