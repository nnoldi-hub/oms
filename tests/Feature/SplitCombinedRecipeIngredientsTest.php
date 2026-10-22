<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitCombinedRecipeIngredientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_splits_combined_ingredients_into_separate_ones_marked_for_configuration(): void
    {
        $menu = Menu::factory()->create([
            'name' => 'Cartofi cu ceapa si boia',
            'ingredients' => [
                ['name' => 'Cartofi', 'quantity_per_person' => 0.3, 'unit' => 'kg', 'estimated_unit_cost' => 4],
                ['name' => 'Ceapa si boia', 'quantity_per_person' => 0.03, 'unit' => 'kg', 'estimated_unit_cost' => 10],
            ],
        ]);

        $this->runSplitMigration();

        $menu->refresh();

        $this->assertSame(
            ['Cartofi', 'Ceapa', 'Boia'],
            collect($menu->ingredients)->pluck('name')->all(),
        );

        $split = collect($menu->ingredients)->whereIn('name', ['Ceapa', 'Boia']);
        $this->assertSame(0.0, (float) $split->sum('quantity_per_person'));
        $this->assertTrue($split->every(fn (array $ingredient): bool => ($ingredient['needs_configuration'] ?? false) === true));
        $this->assertTrue($split->every(fn (array $ingredient): bool => isset($ingredient['ingredient_id'])));
        $this->assertTrue($split->every(fn (array $ingredient): bool => ! array_key_exists('estimated_unit_cost', $ingredient)));

        // Pretul amestecului nu se copiaza pe componente.
        $this->assertNull(
            Ingredient::query()->where('name', 'Ceapa')->where('unit', 'kg')->firstOrFail()->unit_price,
        );
        $this->assertNull(
            Ingredient::query()->where('name', 'Boia')->where('unit', 'kg')->firstOrFail()->unit_price,
        );
    }

    public function test_it_reuses_existing_catalog_prices_for_the_split_parts(): void
    {
        $faina = Ingredient::factory()->create(['name' => 'Faina', 'unit' => 'kg', 'unit_price' => 4]);
        $menu = Menu::factory()->create([
            'name' => 'Negresa de test',
            'ingredients' => [
                ['name' => 'Faina si cacao', 'quantity_per_person' => 0.07, 'unit' => 'kg', 'estimated_unit_cost' => 15],
            ],
        ]);

        $this->runSplitMigration();

        $menu->refresh();

        $fainaEntry = collect($menu->ingredients)->firstWhere('name', 'Faina');
        $this->assertSame($faina->id, $fainaEntry['ingredient_id']);
        $this->assertSame(0.0, (float) $fainaEntry['quantity_per_person']);
        $this->assertTrue($fainaEntry['needs_configuration']);
        $this->assertSame(4.0, (float) $faina->fresh()->unit_price);

        $cacaoEntry = collect($menu->ingredients)->firstWhere('name', 'Cacao');
        $this->assertSame(0.0, (float) $cacaoEntry['quantity_per_person']);
        $this->assertTrue($cacaoEntry['needs_configuration']);
        $this->assertNull(
            Ingredient::query()->where('name', 'Cacao')->where('unit', 'kg')->firstOrFail()->unit_price,
        );
    }

    public function test_it_leaves_menus_without_combined_ingredients_untouched(): void
    {
        $ingredients = [
            ['name' => 'Orez', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 5],
        ];
        $menu = Menu::factory()->create(['name' => 'Orez simplu', 'ingredients' => $ingredients]);

        $this->runSplitMigration();

        $menu->refresh();

        $this->assertSame(['Orez'], collect($menu->ingredients)->pluck('name')->all());
        $this->assertSame(0.1, (float) $menu->ingredients[0]['quantity_per_person']);
    }

    private function runSplitMigration(): void
    {
        $migration = require base_path('database/migrations/2026_10_21_100000_split_combined_recipe_ingredients.php');
        $migration->up();
    }
}
