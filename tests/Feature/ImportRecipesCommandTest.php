<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\Ingredient;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportRecipesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_existing_recipes_and_catalog_prices(): void
    {
        $cartofi = Ingredient::create(['name' => 'Cartofi', 'unit' => 'kg', 'unit_price' => null, 'is_active' => true]);
        $ceapa = Ingredient::create(['name' => 'Ceapa', 'unit' => 'kg', 'unit_price' => 1, 'is_active' => true]);
        $mazare = Ingredient::create(['name' => 'Mazare', 'unit' => 'kg', 'unit_price' => 9.99, 'is_active' => true]);
        Congregation::factory()->create();

        $menu = Menu::create([
            'name' => 'Cartofi taranesti la cuptor cu costita si salata de sfecla',
            'type' => 'main',
            'instructions' => 'Reteta veche',
            'ingredients' => [['name' => 'Cartofi', 'quantity_per_person' => 0.1, 'unit' => 'kg']],
            'allergens' => [],
            'packaging_cost' => 1,
            'is_active' => true,
        ]);

        $path = $this->csv(<<<'CSV'
Reteta;Tip;Ingredient;Cantitate / persoana;Unitate;Cantitate pt. portii de referinta;Pret unitar (RON);Cost / portie (RON)
Cartofi taranesti la cuptor cu costita si salata de sfecla;Fel principal;Cartofi;0,300;kg;12,00;2,00;0,60
Cartofi taranesti la cuptor cu costita si salata de sfecla;Fel principal;Ceapa;0,015;kg;0,60;3,00;0,05
Mazare scazuta cu piept de pui si paine;Fel principal;Mazare;0,150;kg;6,00;;0,00
Mazare scazuta cu piept de pui si paine;Fel principal;Paine;0,080;kg;3,20;;0,00
Nou desert;Desert;Mere;0,200;kg;8,00;5,00;1,00
CSV);

        $this->artisan('oms:import-recipes', ['file' => $path, '--force' => true])->assertSuccessful();

        $menu->refresh();

        $this->assertSame('Reteta veche', $menu->instructions);
        $this->assertSame(2, count($menu->ingredients));
        $this->assertSame($cartofi->id, $menu->ingredients[0]['ingredient_id']);
        $this->assertSame(0.3, (float) $menu->ingredients[0]['quantity_per_person']);
        $this->assertSame($ceapa->id, $menu->ingredients[1]['ingredient_id']);
        $this->assertSame(0.015, (float) $menu->ingredients[1]['quantity_per_person']);

        $this->assertSame(2.0, (float) $cartofi->fresh()->unit_price);
        $this->assertSame(3.0, (float) $ceapa->fresh()->unit_price);

        $mazareMenu = Menu::query()->where('name', 'Mazare scazuta cu piept de pui si paine')->firstOrFail();
        $this->assertSame('main', $mazareMenu->type);
        $this->assertSame(2, count($mazareMenu->ingredients));
        $this->assertSame(9.99, (float) $mazare->fresh()->unit_price);

        $paine = Ingredient::query()->where('name', 'Paine')->where('unit', 'kg')->firstOrFail();
        $this->assertNull($paine->unit_price);

        $desert = Menu::query()->where('name', 'Nou desert')->firstOrFail();
        $this->assertSame('dessert', $desert->type);
        $this->assertSame(5.0, (float) Ingredient::query()->where('name', 'Mere')->firstOrFail()->unit_price);
        $this->assertSame(1, $desert->congregations()->count());
    }

    public function test_dry_run_does_not_change_the_database(): void
    {
        $cartofi = Ingredient::create(['name' => 'Cartofi', 'unit' => 'kg', 'unit_price' => null, 'is_active' => true]);

        $menu = Menu::create([
            'name' => 'Cartofi taranesti la cuptor cu costita si salata de sfecla',
            'type' => 'main',
            'instructions' => 'Reteta veche',
            'ingredients' => [['name' => 'Cartofi', 'quantity_per_person' => 0.1, 'unit' => 'kg']],
            'allergens' => [],
            'packaging_cost' => 0,
            'is_active' => true,
        ]);

        $path = $this->csv(<<<'CSV'
Reteta;Tip;Ingredient;Cantitate / persoana;Unitate;Pret unitar (RON)
Cartofi taranesti la cuptor cu costita si salata de sfecla;Fel principal;Cartofi;0,300;kg;2,00
CSV);

        $this->artisan('oms:import-recipes', ['file' => $path, '--dry-run' => true])
            ->expectsOutputToContain('nu s-a modificat nimic')
            ->assertSuccessful();

        $this->assertSame(0.1, (float) $menu->fresh()->ingredients[0]['quantity_per_person']);
        $this->assertNull($cartofi->fresh()->unit_price);
    }

    public function test_it_rejects_invalid_units_without_changing_anything(): void
    {
        $path = $this->csv(<<<'CSV'
Reteta;Tip;Ingredient;Cantitate / persoana;Unitate;Pret unitar (RON)
Reteta noua;Fel principal;Cartofi;0,300;cutie;2,00
CSV);

        $this->artisan('oms:import-recipes', ['file' => $path, '--force' => true])
            ->expectsOutputToContain('unitatea')
            ->assertFailed();

        $this->assertSame(0, Menu::query()->count());
        $this->assertFalse(Ingredient::query()->where('name', 'Cartofi')->exists());
    }

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'retete_');
        file_put_contents($path, $content);

        return $path;
    }
}
