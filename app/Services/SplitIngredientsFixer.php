<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Menu;
use App\Support\CombinedIngredients;
use Illuminate\Support\Collection;

/**
 * Analizeaza retetele afectate de impartirea ingredientelor combinate.
 *
 * Impartirea s-a facut pe baza cantitatii amestecului, deci componentele
 * au primit cantitati automate (jumatate din cantitatea amestecului) si,
 * uneori, pretul amestecului. Componentele care nu au fost corectate manual
 * raman "de configurat", iar cele corectate nu se ating.
 */
class SplitIngredientsFixer
{
    public const STATE_PENDING = 'de_marcat';

    public const STATE_MARKED = 'deja_marcat';

    public const STATE_MANUAL = 'corectat_manual';

    /**
     * Analiza completa: retetele afectate, componentele lor si starea fiecareia.
     *
     * @return array{
     *     recipes: list<array{menu: Menu, combined: string, state: string, components: list<array{name: string, unit: string, quantity_per_person: float, catalog_price: float|null, price_inherited: bool, needs_configuration: bool}>}>,
     *     unpriced: list<array{menu: Menu, name: string, unit: string, quantity_per_person: float}>,
     *     obsolete_catalog: list<Ingredient>
     * }
     */
    public function analyze(): array
    {
        $catalog = Ingredient::query()->get();
        $recipes = [];
        $unpriced = [];

        foreach (Menu::query()->orderBy('id')->get() as $menu) {
            $ingredients = $menu->ingredients ?? [];

            foreach ($this->groups($menu, $ingredients, $catalog) as $group) {
                $recipes[] = $group;
            }

            foreach ($this->missingPrices($ingredients, $catalog) as $missing) {
                $unpriced[] = ['menu' => $menu] + $missing;
            }
        }

        return [
            'recipes' => $recipes,
            'unpriced' => $unpriced,
            'obsolete_catalog' => $this->obsoleteCatalogEntries($catalog),
        ];
    }

    /**
     * Marcheaza componentele necorectate manual ca "de configurat".
     *
     * Nu modifica cantitatile si nu atinge componentele corectate manual,
     * deci comanda poate fi rulata de mai multe ori.
     *
     * @param  array<string, mixed>  $analysis
     * @return list<array{menu_id: int, menu: string, components: list<string>}>
     */
    public function apply(array $analysis): array
    {
        $applied = [];

        foreach ($analysis['recipes'] as $recipe) {
            if ($recipe['state'] !== self::STATE_PENDING) {
                continue;
            }

            $menu = $recipe['menu'];
            $ingredients = $menu->ingredients ?? [];
            $changed = [];

            foreach ($ingredients as $index => $ingredient) {
                if (! $this->isGroupComponent($recipe['components'], $ingredient)) {
                    continue;
                }

                $ingredients[$index]['needs_configuration'] = true;
                $changed[] = (string) ($ingredient['name'] ?? '');
            }

            if ($changed === []) {
                continue;
            }

            $menu->ingredients = $ingredients;
            $menu->save();

            $applied[] = [
                'menu_id' => $menu->id,
                'menu' => (string) $menu->name,
                'components' => $changed,
            ];
        }

        return $applied;
    }

    /**
     * Grupurile de componente rezultate din impartire, pentru fiecare reteta.
     *
     * O reteta este considerata afectata daca toate componentele unui amestec
     * cunoscut apar in reteta, cu aceeasi unitate, iar amestecul original
     * exista inca in catalog.
     *
     * @param  array<int, array<string, mixed>>  $ingredients
     * @param  Collection<int, Ingredient>  $catalog
     * @return list<array{menu: Menu, combined: string, state: string, components: list<array<string, mixed>>}>
     */
    private function groups(Menu $menu, array $ingredients, Collection $catalog): array
    {
        $groups = [];

        foreach (CombinedIngredients::SPLITS as $combined => $parts) {
            $legacy = $catalog->first(
                fn (Ingredient $ingredient): bool => mb_strtolower($ingredient->name) === mb_strtolower($combined),
            );

            if ($legacy === null) {
                continue;
            }

            $matched = [];

            foreach ($parts as $part) {
                $index = $this->indexOfComponent($ingredients, $part);

                if ($index === null) {
                    $matched = [];

                    break;
                }

                $matched[] = $index;
            }

            if ($matched === []) {
                continue;
            }

            $units = array_map(fn (int $index): string => (string) ($ingredients[$index]['unit'] ?? ''), $matched);

            if (count(array_unique($units)) !== 1) {
                // Componentele cu unitati diferite nu provin din impartire.
                continue;
            }

            $components = [];

            foreach ($matched as $index) {
                $ingredient = $ingredients[$index];
                $catalogIngredient = $this->catalogIngredient($catalog, $ingredient);
                $price = $catalogIngredient?->unit_price;

                $components[] = [
                    'name' => (string) ($ingredient['name'] ?? ''),
                    'unit' => (string) ($ingredient['unit'] ?? ''),
                    'quantity_per_person' => (float) ($ingredient['quantity_per_person'] ?? 0),
                    'catalog_price' => $price === null ? null : (float) $price,
                    'price_inherited' => $price !== null
                        && $legacy->unit_price !== null
                        && abs((float) $price - (float) $legacy->unit_price) < 0.001,
                    'needs_configuration' => (bool) ($ingredient['needs_configuration'] ?? false),
                ];
            }

            $groups[] = [
                'menu' => $menu,
                'combined' => $combined,
                'state' => $this->state($components),
                'components' => $components,
            ];
        }

        return $groups;
    }

    /**
     * Starea unei componente: corectata manual (cantitatile diferite),
     * deja marcata sau inca de marcat.
     *
     * @param  list<array<string, mixed>>  $components
     */
    private function state(array $components): string
    {
        if (collect($components)->contains('needs_configuration', true)) {
            return self::STATE_MARKED;
        }

        $quantities = array_map(fn (array $component): float => (float) $component['quantity_per_person'], $components);

        if (count(array_unique($quantities)) > 1) {
            // Cantitatile nu mai sunt egale: componenta a fost corectata manual.
            return self::STATE_MANUAL;
        }

        return self::STATE_PENDING;
    }

    /**
     * Componentele retetei care nu au pret in catalog (informativ).
     *
     * @param  array<int, array<string, mixed>>  $ingredients
     * @param  Collection<int, Ingredient>  $catalog
     * @return list<array{name: string, unit: string, quantity_per_person: float}>
     */
    private function missingPrices(array $ingredients, Collection $catalog): array
    {
        $missing = [];

        foreach ($ingredients as $ingredient) {
            $catalogIngredient = $this->catalogIngredient($catalog, $ingredient);

            if ($catalogIngredient === null || $catalogIngredient->unit_price !== null) {
                continue;
            }

            $missing[] = [
                'name' => (string) ($ingredient['name'] ?? ''),
                'unit' => (string) ($ingredient['unit'] ?? ''),
                'quantity_per_person' => (float) ($ingredient['quantity_per_person'] ?? 0),
            ];
        }

        return $missing;
    }

    /**
     * Ingredientele combinate ramase in catalog, fara a mai fi folosite.
     *
     * @param  Collection<int, Ingredient>  $catalog
     * @return list<Ingredient>
     */
    private function obsoleteCatalogEntries(Collection $catalog): array
    {
        $names = collect(CombinedIngredients::SPLITS)->keys()
            ->map(fn (string $name): string => mb_strtolower($name));

        return $catalog
            ->filter(fn (Ingredient $ingredient): bool => $names->contains(mb_strtolower($ingredient->name)))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $ingredients
     */
    private function indexOfComponent(array $ingredients, string $part): ?int
    {
        foreach ($ingredients as $index => $ingredient) {
            if (mb_strtolower(trim((string) ($ingredient['name'] ?? ''))) === mb_strtolower($part)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $ingredient
     * @param  Collection<int, Ingredient>  $catalog
     */
    private function catalogIngredient(Collection $catalog, array $ingredient): ?Ingredient
    {
        $ingredientId = $ingredient['ingredient_id'] ?? null;

        if ($ingredientId !== null) {
            return $catalog->first(fn (Ingredient $catalogIngredient): bool => $catalogIngredient->id === (int) $ingredientId);
        }

        return $catalog->first(
            fn (Ingredient $catalogIngredient): bool => mb_strtolower($catalogIngredient->name) === mb_strtolower(trim((string) ($ingredient['name'] ?? '')))
                && $catalogIngredient->unit === (string) ($ingredient['unit'] ?? ''),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $components
     * @param  array<string, mixed>  $ingredient
     */
    private function isGroupComponent(array $components, array $ingredient): bool
    {
        $name = mb_strtolower(trim((string) ($ingredient['name'] ?? '')));

        foreach ($components as $component) {
            if (mb_strtolower((string) $component['name']) === $name) {
                return true;
            }
        }

        return false;
    }
}
