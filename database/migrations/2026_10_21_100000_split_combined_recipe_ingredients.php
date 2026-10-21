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
                $parts = CombinedIngredients::partsFor((string) ($ingredient['name'] ?? ''));

                if ($parts === null) {
                    $split[] = $ingredient;

                    continue;
                }

                $changed = true;
                $quantity = (float) ($ingredient['quantity_per_person'] ?? 0);
                $unit = (string) ($ingredient['unit'] ?? 'kg');
                $combinedIngredient = isset($ingredient['ingredient_id'])
                    ? Ingredient::query()->find($ingredient['ingredient_id'])
                    : null;
                $estimatedUnitCost = $ingredient['estimated_unit_cost'] ?? $combinedIngredient?->unit_price;

                foreach ($parts as $part) {
                    $entry = [
                        'name' => $part,
                        'quantity_per_person' => round($quantity / count($parts), 4),
                        'unit' => $unit,
                    ];

                    $catalogIngredient = Ingredient::query()
                        ->where('name', $part)
                        ->where('unit', $unit)
                        ->first();

                    if ($catalogIngredient !== null) {
                        $entry['ingredient_id'] = $catalogIngredient->id;
                    } elseif ($estimatedUnitCost !== null) {
                        $entry['estimated_unit_cost'] = (float) $estimatedUnitCost;
                    }

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
