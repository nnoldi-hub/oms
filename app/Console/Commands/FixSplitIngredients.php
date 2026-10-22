<?php

namespace App\Console\Commands;

use App\Services\SplitIngredientsFixer;
use Illuminate\Console\Command;

class FixSplitIngredients extends Command
{
    protected $signature = 'oms:fix-split-ingredients
        {--dry-run : Afiseaza retetele afectate fara sa modifice nimic}
        {--force : Nu cere confirmarea finala}';

    protected $description = 'Marcheaza ca "de configurat" componentele rezultate din impartirea ingredientelor combinate';

    public function handle(SplitIngredientsFixer $fixer): int
    {
        $analysis = $fixer->analyze();

        if ($analysis['recipes'] === []) {
            $this->info('Nu exista retete afectate de impartirea ingredientelor combinate.');

            return self::SUCCESS;
        }

        $this->info('Retete afectate de impartire:');
        $this->newLine();

        $rows = [];

        foreach ($analysis['recipes'] as $recipe) {
            foreach ($recipe['components'] as $component) {
                $rows[] = [
                    $recipe['menu']->name,
                    $recipe['combined'],
                    $component['name'],
                    $component['unit'],
                    rtrim(rtrim(number_format($component['quantity_per_person'], 3, '.', ''), '0'), '.'),
                    $component['catalog_price'] === null ? 'de configurat' : number_format($component['catalog_price'], 2, '.', ' '),
                    $component['price_inherited'] ? 'da' : '-',
                    $this->stateLabel($recipe['state']),
                ];
            }
        }

        $this->table(
            ['Reteta', 'Amestec', 'Componenta', 'Unitate', 'Cantitate/persoana', 'Pret catalog', 'Pret mostenit', 'Stare'],
            $rows,
        );

        $pending = array_values(array_filter(
            $analysis['recipes'],
            fn (array $recipe): bool => $recipe['state'] === SplitIngredientsFixer::STATE_PENDING,
        ));

        $manual = array_values(array_filter(
            $analysis['recipes'],
            fn (array $recipe): bool => $recipe['state'] === SplitIngredientsFixer::STATE_MANUAL,
        ));

        $this->newLine();
        $this->info(sprintf(
            '%d retete de marcat (%d componente impartite), %d retete deja corectate manual.',
            count(array_unique(array_map(fn (array $recipe): int => $recipe['menu']->id, $pending))),
            count(array_map(fn (array $recipe): int => count($recipe['components']), $pending)),
            count($manual),
        ));

        if ($analysis['unpriced'] !== []) {
            $this->newLine();
            $this->warn('Ingrediente fara pret in catalog (nu sunt modificate de aceasta comanda):');

            $grouped = [];

            foreach ($analysis['unpriced'] as $item) {
                $grouped[$item['menu']->name][] = $item['name'].' ('.$item['unit'].')';
            }

            $this->table(
                ['Reteta', 'Ingrediente fara pret'],
                array_map(
                    fn (string $menu, array $names): array => [$menu, implode(', ', $names)],
                    array_keys($grouped),
                    array_values($grouped),
                ),
            );
        }

        if ($analysis['obsolete_catalog'] !== []) {
            $this->newLine();
            $this->warn('Amestecele originale au ramas in catalog si nu mai sunt folosite in retete:');

            $this->table(
                ['Ingredient', 'Unitate', 'Pret catalog'],
                array_map(fn ($ingredient): array => [
                    $ingredient->name,
                    $ingredient->unit,
                    $ingredient->unit_price === null ? 'de configurat' : number_format((float) $ingredient->unit_price, 2, '.', ' '),
                ], $analysis['obsolete_catalog']),
            );
        }

        if ($pending === []) {
            $this->newLine();
            $this->info('Nu este nimic de marcat.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Mod de testare (--dry-run): nu s-a modificat nimic.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Marchezi componentele de mai sus ca "de configurat"?', true)) {
            $this->info('Operatiunea a fost anulata.');

            return self::SUCCESS;
        }

        $applied = $fixer->apply($analysis);

        $this->newLine();

        foreach ($applied as $menu) {
            $this->info(sprintf('Marcat: %s (%s)', $menu['menu'], implode(', ', $menu['components'])));
        }

        $this->info(sprintf('%d retete au fost actualizate.', count($applied)));
        $this->line('Cantitatile si preturile raman pe loc: introdu valorile corecte din panel.');

        return self::SUCCESS;
    }

    private function stateLabel(string $state): string
    {
        return match ($state) {
            SplitIngredientsFixer::STATE_PENDING => 'de marcat',
            SplitIngredientsFixer::STATE_MARKED => 'deja marcat',
            default => 'corectat manual',
        };
    }
}
