<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    /** @use HasFactory<\Database\Factories\IngredientFactory> */
    use HasFactory;

    public const UNITS = ['kg', 'g', 'l', 'buc'];

    protected $fillable = ['name', 'unit', 'unit_price', 'package_quantity', 'has_no_allergens', 'fixed_cost_per_portion', 'is_active'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'package_quantity' => 'decimal:3',
            'has_no_allergens' => 'boolean',
            'fixed_cost_per_portion' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Cantitatea cumparata intr-un pachet, exprimata in unitatea ingredientului.
     */
    public function packageQuantity(): ?float
    {
        return $this->package_quantity === null ? null : (float) $this->package_quantity;
    }
}