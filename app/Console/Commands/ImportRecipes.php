<?php

namespace App\Console\Commands;

use App\Models\Ingredient;
use App\Models\Menu;
use App\Services\RecipeImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportRecipes extends Command
{
    protected $signature = 'oms:import-recipes
        {file? : Fisierul CSV sau XLSX cu retete}
        {--dry-run : Afiseaza modificarile fara sa salveze}
        {--force : Nu cere confirmarea finala}';

    protected $description = 'Actualizeaza retetele, ingredientele si preturile din catalogul global';

    public function handle(RecipeImportService $importer): int
    {
        $path = $this->argument('file') ?: database_path('data/retete.csv');

        if (! is_file($path)) {
            $this->error("Fisierul nu exista: {$path}");

            return self::FAILURE;
        }

        try {
            $plan = $importer->plan($importer->read($path));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($plan['errors'] !== []) {
            $this->error('Fisierul contine erori. Nu s-a modificat nimic:');

            foreach ($plan['errors'] as $error) {
                $this->line(' - '.$error);
            }

            return self::FAILURE;
        }

        if ($plan['recipes'] === []) {
            $this->warn('Fisierul nu contine retete.');

            return self::SUCCESS;
        }

        $existingMenus = Menu::query()
            ->whereIn('name', array_column($plan['recipes'], 'name'))
            ->pluck('name')
            ->flip();

        $catalog = Ingredient::query()->get()->keyBy(
            fn (Ingredient $ingredient): string => $importer->ingredientKey($ingredient->name, $ingredient->unit)
        );

        $rows = [];

        foreach ($plan['recipes'] as $recipe) {
            $priced = 0;

            foreach ($recipe['ingredients'] as $ingredient) {
                $key = $importer->ingredientKey($ingredient['name'], $ingredient['unit']);

                if (isset($plan['prices'][$key])) {
                    $priced++;
                }
            }

            $ingredientCount = count($recipe['ingredients']);

            $rows[] = [
                $recipe['name'],
                $this->typeLabel($recipe['type']),
                (string) $ingredientCount,
                (string) $priced,
                (string) ($ingredientCount - $priced),
                $existingMenus->has($recipe['name']) ? 'actualizeaza' : 'creeaza',
            ];
        }

        $this->table(
            ['Reteta', 'Tip', 'Ingrediente', 'Cu pret in fisier', 'Fara pret in fisier', 'Actiune'],
            $rows,
        );

        if ($plan['unpriced'] !== []) {
            $this->newLine();
            $this->warn('Ingrediente fara pret in fisier (pretul existent din catalog ramane neschimbat):');

            $this->table(
                ['Ingredient', 'Unitate', 'Pret in catalog'],
                array_map(
                    function (array $ingredient) use ($catalog, $importer): array {
                        $catalogIngredient = $catalog->get($importer->ingredientKey($ingredient['name'], $ingredient['unit']));

                        return [
                            $ingredient['name'],
                            $ingredient['unit'],
                            $catalogIngredient?->unit_price === null
                                ? 'lipsa'
                                : number_format((float) $catalogIngredient->unit_price, 2, '.', ' ').' RON',
                        ];
                    },
                    $plan['unpriced'],
                ),
            );
        }

        $this->newLine();
        $this->info(sprintf(
            '%d retete, %d ingrediente, %d preturi in fisier.',
            count($plan['recipes']),
            array_sum(array_map(fn (array $recipe): int => count($recipe['ingredients']), $plan['recipes'])),
            count($plan['prices']),
        ));

        if ($this->option('dry-run')) {
            $this->info('Mod de testare (--dry-run): nu s-a modificat nimic.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Aplici actualizarile in baza de date?', true)) {
            $this->info('Operatiunea a fost anulata.');

            return self::SUCCESS;
        }

        $stats = $importer->apply($plan);

        $this->newLine();
        $this->info(sprintf(
            'Retete create: %d; retete actualizate: %d; ingrediente create: %d; preturi actualizate: %d.',
            $stats['recipes_created'],
            $stats['recipes_updated'],
            $stats['ingredients_created'],
            $stats['prices_updated'],
        ));

        return self::SUCCESS;
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'main' => 'Fel principal',
            'soup' => 'Ciorba',
            'dessert' => 'Desert',
            default => $type,
        };
    }
}
