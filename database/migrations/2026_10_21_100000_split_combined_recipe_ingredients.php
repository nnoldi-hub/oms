<?php

use App\Models\Ingredient;
use App\Models\Menu;
use App\Support\CombinedIngredients;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Menu::query()->orderBy('id')->each(function (Menu $menu): void {
            $ingredients = $menu->ingredients ?? [];
            $changed = false;
            $split = [];

            foreach ($ingredients as $ingredient) {
                $parts = CombinedIngredients::splitFor($ingredient);

                if ($parts === null) {
                    $split[] = $ingredient;

                    continue;
                }

                $changed = true;
                $unit = (string) ($ingredient['unit'] ?? 'kg');

                foreach ($parts as $part) {
                    $entry = $part;
                    $catalogIngredient = Ingredient::query()
                        ->where('name', $part['name'])
                        ->where('unit', $unit)
                        ->first();

                    if ($catalogIngredient !== null) {
                        $entry['ingredient_id'] = $catalogIngredient->id;
                    }

                    // Pretul amestecului nu se mosteneste: fiecare componenta ramane de configurat.
                    unset($entry['estimated_unit_cost']);

                    $split[] = $entry;
                }
            }

            if ($changed) {
                $menu->ingredients = $split;
                $menu->save();
            }
        });
    }

    public function down(): void
    {
        // Datele raman imparti pe ingrediente separate; nu se pot reconstitui automat.
    }
};
