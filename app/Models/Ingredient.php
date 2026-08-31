<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    /** @use HasFactory<\Database\Factories\IngredientFactory> */
    use HasFactory;

    public const UNITS = ['kg', 'g', 'l', 'buc'];

    protected $fillable = ['name', 'unit', 'unit_price', 'is_active'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'is_active' => 'boolean'];
    }
}