<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

class Menu extends Model
{
    /** @use HasFactory<\Database\Factories\MenuFactory> */
    use HasFactory;

    public const ALLERGENS = [
        'Gluten', 'Crustacee', 'Oua', 'Peste', 'Arahide', 'Soia', 'Lapte',
        'Fructe cu coaja', 'Telina', 'Mustar', 'Susan', 'Sulfiti', 'Lupin', 'Moluste',
    ];

    protected $fillable = ['name', 'type', 'instructions', 'ingredients', 'allergens', 'packaging_cost', 'is_active'];

    protected function casts(): array
    {
        return [
            'ingredients' => 'array',
            'allergens' => 'array',
            'packaging_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Menu $menu): void {
            $ingredientIds = collect($menu->ingredients)->pluck('ingredient_id')->filter()->unique();
            $catalogIngredients = $ingredientIds->isEmpty()
                ? collect()
                : Ingredient::query()->whereIn('id', $ingredientIds)->get()->keyBy('id');
            $recipeIngredients = $menu->ingredients ?? [];

            foreach ($recipeIngredients as &$ingredient) {
                $catalogIngredient = $catalogIngredients->get($ingredient['ingredient_id'] ?? null);

                if ($catalogIngredient !== null) {
                    $ingredient['name'] = $catalogIngredient->name;
                    $ingredient['unit'] = $catalogIngredient->unit;
                    unset($ingredient['estimated_unit_cost']);
                }

                if ($catalogIngredient === null && filled($ingredient['name'] ?? null) && filled($ingredient['unit'] ?? null)) {
                    $catalogIngredient = Ingredient::firstOrCreate(
                        ['name' => trim((string) $ingredient['name']), 'unit' => $ingredient['unit']],
                        ['unit_price' => $ingredient['estimated_unit_cost'] ?? null, 'is_active' => true],
                    );
                    $ingredient['ingredient_id'] = $catalogIngredient->id;
                    unset($ingredient['estimated_unit_cost']);
                }

                if (
                    blank($ingredient['name'] ?? null)
                    || ! is_numeric($ingredient['quantity_per_person'] ?? null)
                    || (float) $ingredient['quantity_per_person'] <= 0
                    || ! in_array($ingredient['unit'] ?? null, ['kg', 'g', 'l', 'buc'], true)
                    || (array_key_exists('estimated_unit_cost', $ingredient)
                        && $ingredient['estimated_unit_cost'] !== null
                        && (! is_numeric($ingredient['estimated_unit_cost']) || (float) $ingredient['estimated_unit_cost'] < 0))
                ) {
                    throw ValidationException::withMessages([
                        'ingredients' => 'Fiecare ingredient necesita denumire, cantitate pozitiva si unitate valida.',
                    ]);
                }
            }

            $menu->ingredients = $recipeIngredients;

            if ($menu->allergens !== null && array_diff($menu->allergens, self::ALLERGENS) !== []) {
                throw ValidationException::withMessages([
                    'allergens' => 'Lista de alergeni contine o valoare nepermisa.',
                ]);
            }
        });
    }

    public function dailyMeals(): HasMany
    {
        return $this->hasMany(DailyMeal::class);
    }

    public function soupDailyMeals(): HasMany
    {
        return $this->hasMany(DailyMeal::class, 'soup_menu_id');
    }

    public function dessertDailyMeals(): HasMany
    {
        return $this->hasMany(DailyMeal::class, 'dessert_menu_id');
    }

    public function congregations(): BelongsToMany
    {
        return $this->belongsToMany(Congregation::class)->withTimestamps();
    }
}
