<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('unit', 10);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'unit']);
        });

        DB::table('menus')->orderBy('id')->each(function (object $menu): void {
            $recipeIngredients = json_decode($menu->ingredients, true) ?: [];

            foreach ($recipeIngredients as &$recipeIngredient) {
                $name = trim((string) ($recipeIngredient['name'] ?? ''));
                $unit = (string) ($recipeIngredient['unit'] ?? '');

                if ($name === '' || $unit === '') {
                    continue;
                }

                $ingredientId = DB::table('ingredients')->where('name', $name)->where('unit', $unit)->value('id');

                if ($ingredientId === null) {
                    $ingredientId = DB::table('ingredients')->insertGetId([
                        'name' => $name,
                        'unit' => $unit,
                        'unit_price' => is_numeric($recipeIngredient['estimated_unit_cost'] ?? null) ? $recipeIngredient['estimated_unit_cost'] : null,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $recipeIngredient['ingredient_id'] = $ingredientId;
                unset($recipeIngredient['estimated_unit_cost']);
            }

            DB::table('menus')->where('id', $menu->id)->update(['ingredients' => json_encode($recipeIngredients)]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};