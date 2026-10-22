<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixSplitIngredientsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_the_affected_recipes_without_changing_anything_in_dry_run(): void
    {
        $this->splitRecipe();

        $this->artisan('oms:fix-split-ingredients --dry-run')
            ->expectsOutputToContain('de marcat')
            ->assertSuccessful();

        $menu = Menu::query()->where('name', 'Reteta de test')->firstOrFail();

        $this->assertFalse((bool) ($menu->ingredients[0]['needs_configuration'] ?? false));
        $this->assertFalse((bool) ($menu->ingredients[1]['needs_configuration'] ?? false));
    }

    public function test_it_marks_the_split_components_as_to_configure(): void
    {
        $this->splitRecipe();

        $this->artisan('oms:fix-split-ingredients --force')->assertSuccessful();

        $menu = Menu::query()->where('name', 'Reteta de test')->firstOrFail();

        $this->assertTrue((bool) ($menu->ingredients[0]['needs_configuration'] ?? false));
        $this->assertTrue((bool) ($menu->ingredients[1]['needs_configuration'] ?? false));
        $this->assertSame(0.025, (float) $menu->ingredients[0]['quantity_per_person']);
    }

    public function test_it_can_be_run_several_times_without_changing_the_corrected_components(): void
    {
        $this->splitRecipe();

        $this->artisan('oms:fix-split-ingredients --force')->assertSuccessful();
        $this->artisan('oms:fix-split-ingredients --force')->assertSuccessful();

        $menu = Menu::query()->where('name', 'Reteta de test')->firstOrFail();

        $this->assertTrue((bool) ($menu->ingredients[0]['needs_configuration'] ?? false));
        $this->assertSame(0.025, (float) $menu->ingredients[0]['quantity_per_person']);
    }

    public function test_it_does_not_touch_the_components_corrected_by_hand(): void
    {
        $this->splitRecipe();

        $menu = Menu::query()->where('name', 'Reteta de test')->firstOrFail();
        $ingredients = $menu->ingredients;
        $ingredients[0]['quantity_per_person'] = 0.03;
        $ingredients[1]['quantity_per_person'] = 0.02;
        $menu->ingredients = $ingredients;
        $menu->save();

        $this->artisan('oms:fix-split-ingredients --force')->assertSuccessful();

        $menu->refresh();

        $this->assertFalse((bool) ($menu->ingredients[0]['needs_configuration'] ?? false));
        $this->assertFalse((bool) ($menu->ingredients[1]['needs_configuration'] ?? false));
        $this->assertSame(0.03, (float) $menu->ingredients[0]['quantity_per_person']);
    }

    /**
     * Reteta cu componentele impartite si amestecul original inca in catalog.
     */
    private function splitRecipe(): void
    {
        Ingredient::create(['name' => 'Ceapa si morcovi', 'unit' => 'kg', 'unit_price' => null, 'is_active' => true]);

        $ceapa = Ingredient::create(['name' => 'Ceapa', 'unit' => 'kg', 'unit_price' => null, 'is_active' => true]);
        $morcovi = Ingredient::create(['name' => 'Morcovi', 'unit' => 'kg', 'unit_price' => null, 'is_active' => true]);

        Menu::create([
            'name' => 'Reteta de test',
            'type' => 'main',
            'instructions' => 'Test',
            'ingredients' => [
                ['ingredient_id' => $ceapa->id, 'name' => 'Ceapa', 'quantity_per_person' => 0.025, 'unit' => 'kg'],
                ['ingredient_id' => $morcovi->id, 'name' => 'Morcovi', 'quantity_per_person' => 0.025, 'unit' => 'kg'],
            ],
            'allergens' => [],
            'packaging_cost' => 1,
            'is_active' => true,
        ]);
    }
}
