<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Services\IngredientMerger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class IngredientMergerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Daca inserarea esueaza pentru ca tabelul menus cere alte coloane obligatorii,
     * adauga-le aici (de exemplu "type"), copiind valorile din MenuCatalogSeeder.
     */
    private function menuWith(array $lines): int
    {
        return DB::table('menus')->insertGetId([
            'name' => 'Reteta test',
            'ingredients' => json_encode($lines, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function linesOf(int $menuId): array
    {
        return json_decode((string) DB::table('menus')->where('id', $menuId)->value('ingredients'), true);
    }

    public function test_merge_moves_recipes_to_kept_ingredient(): void
    {
        $keep = Ingredient::create(['name' => 'Orez', 'unit' => 'kg', 'unit_price' => 6.79, 'is_active' => true]);
        $duplicate = Ingredient::create(['name' => 'Orez rotund', 'unit' => 'kg', 'unit_price' => 6.79, 'is_active' => true]);

        $menuId = $this->menuWith([
            ['name' => 'Orez rotund', 'quantity_per_person' => 0.08, 'unit' => 'kg', 'ingredient_id' => $duplicate->id],
        ]);

        $stats = (new IngredientMerger)->merge($duplicate, $keep);

        $lines = $this->linesOf($menuId);
        $this->assertSame(1, $stats['menus']);
        $this->assertSame($keep->id, $lines[0]['ingredient_id']);
        $this->assertSame('Orez', $lines[0]['name']);
        $this->assertFalse($duplicate->fresh()->is_active);
        $this->assertTrue($keep->fresh()->is_active);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $keep = Ingredient::create(['name' => 'Orez', 'unit' => 'kg', 'unit_price' => 6.79, 'is_active' => true]);
        $duplicate = Ingredient::create(['name' => 'Orez rotund', 'unit' => 'kg', 'unit_price' => 6.79, 'is_active' => true]);

        $menuId = $this->menuWith([
            ['name' => 'Orez rotund', 'quantity_per_person' => 0.08, 'unit' => 'kg', 'ingredient_id' => $duplicate->id],
        ]);

        $stats = (new IngredientMerger)->merge($duplicate, $keep, dryRun: true);

        $this->assertSame(1, $stats['menus']);
        $this->assertSame($duplicate->id, $this->linesOf($menuId)[0]['ingredient_id']);
        $this->assertTrue($duplicate->fresh()->is_active);
    }

    public function test_same_ingredient_twice_in_a_recipe_is_combined(): void
    {
        $keep = Ingredient::create(['name' => 'Legume pentru ciorba', 'unit' => 'kg', 'unit_price' => 12, 'is_active' => true]);
        $duplicate = Ingredient::create(['name' => 'Mix legume', 'unit' => 'kg', 'unit_price' => 12, 'is_active' => true]);

        $menuId = $this->menuWith([
            ['name' => 'Legume pentru ciorba', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'ingredient_id' => $keep->id],
            ['name' => 'Mix legume', 'quantity_per_person' => 0.05, 'unit' => 'kg', 'ingredient_id' => $duplicate->id],
        ]);

        $stats = (new IngredientMerger)->merge($duplicate, $keep);

        $lines = $this->linesOf($menuId);
        $this->assertCount(1, $lines);
        $this->assertEqualsWithDelta(0.15, $lines[0]['quantity_per_person'], 0.0001);
        $this->assertSame(1, $stats['combined']);
    }

    public function test_different_units_are_refused(): void
    {
        $keep = Ingredient::create(['name' => 'Oua', 'unit' => 'buc', 'unit_price' => 1.2, 'is_active' => true]);
        $duplicate = Ingredient::create(['name' => 'Oua (kg)', 'unit' => 'kg', 'unit_price' => 1.2, 'is_active' => true]);

        $this->expectException(InvalidArgumentException::class);

        (new IngredientMerger)->merge($duplicate, $keep);
    }

    public function test_cannot_merge_ingredient_into_itself(): void
    {
        $ingredient = Ingredient::create(['name' => 'Orez', 'unit' => 'kg', 'unit_price' => 6.79, 'is_active' => true]);

        $this->expectException(InvalidArgumentException::class);

        (new IngredientMerger)->merge($ingredient, $ingredient);
    }
}