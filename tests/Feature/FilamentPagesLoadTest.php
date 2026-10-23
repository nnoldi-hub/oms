<?php

namespace Tests\Feature;

use App\Filament\Resources\DailyMeals\Pages\CreateDailyMeal;
use App\Filament\Resources\DailyMeals\Pages\EditDailyMeal;
use App\Filament\Resources\DailyMeals\Pages\ViewDailyMeal;
use App\Filament\Resources\Ingredients\Pages\CreateIngredient;
use App\Filament\Resources\Ingredients\Pages\EditIngredient;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\Pages\ViewMenu;
use App\Filament\Resources\Weeks\Pages\CreateWeek;
use App\Filament\Resources\Weeks\Pages\EditWeek;
use App\Filament\Resources\Weeks\Pages\ViewWeek;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\User;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Verifica ca paginile Filament se incarca fara erori de tip 500.
 *
 * Aceste teste prind erorile de import (de exemplu Get din namespace-ul gresit)
 * care apar doar la randarea formularului.
 */
class FilamentPagesLoadTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_menu_pages_load(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $menu = Menu::factory()->create();

        Livewire::actingAs($administrator)
            ->test(CreateMenu::class)
            ->assertSuccessful();

        Livewire::actingAs($administrator)
            ->test(EditMenu::class, ['record' => $menu->getKey()])
            ->assertSuccessful();

        Livewire::actingAs($administrator)
            ->test(ViewMenu::class, ['record' => $menu->getKey()])
            ->assertSuccessful();
    }

    public function test_the_daily_meal_pages_load(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $dailyMeal = DailyMeal::factory()->create();

        Livewire::actingAs($administrator)
            ->test(CreateDailyMeal::class)
            ->assertSuccessful();

        Livewire::actingAs($administrator)
            ->test(EditDailyMeal::class, ['record' => $dailyMeal->getKey()])
            ->assertSuccessful();

        Livewire::actingAs($administrator)
            ->test(ViewDailyMeal::class, ['record' => $dailyMeal->getKey()])
            ->assertSuccessful();
    }

    public function test_the_week_pages_load(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $week = Week::factory()->create();

        Livewire::actingAs($administrator)
            ->test(CreateWeek::class)
            ->assertSuccessful();

        Livewire::actingAs($administrator)
            ->test(EditWeek::class, ['record' => $week->getKey()])
            ->assertSuccessful();

        Livewire::actingAs($administrator)
            ->test(ViewWeek::class, ['record' => $week->getKey()])
            ->assertSuccessful();
    }

    public function test_the_ingredient_pages_load(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $ingredient = Ingredient::create(['name' => 'Cartofi', 'unit' => 'kg', 'unit_price' => 4, 'is_active' => true]);

        Livewire::actingAs($administrator)
            ->test(CreateIngredient::class)
            ->assertSuccessful();

        Livewire::actingAs($administrator)
            ->test(EditIngredient::class, ['record' => $ingredient->getKey()])
            ->assertSuccessful();
    }

    public function test_the_ingredient_form_persists_the_package_allergen_and_fixed_cost_fields(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($administrator)
            ->test(CreateIngredient::class)
            ->fillForm([
                'name' => 'Ingredient test pachet',
                'unit' => 'kg',
                'unit_price' => 7.5,
                'package_quantity' => 5,
                'has_no_allergens' => true,
                'fixed_cost_per_portion' => true,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $ingredient = Ingredient::query()->where('name', 'Ingredient test pachet')->firstOrFail();

        $this->assertSame(5.0, (float) $ingredient->package_quantity);
        $this->assertTrue((bool) $ingredient->has_no_allergens);
        $this->assertTrue((bool) $ingredient->fixed_cost_per_portion);
    }

    public function test_the_menu_form_keeps_the_quantity_optional_for_the_components_to_configure(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $ingredient = Ingredient::create(['name' => 'Cartofi test', 'unit' => 'kg', 'unit_price' => 4, 'is_active' => true]);

        Livewire::actingAs($administrator)
            ->test(CreateMenu::class)
            ->fillForm([
                'name' => 'Fel de configurat',
                'type' => 'main',
                'packaging_cost' => 0,
                'ingredients' => [[
                    'ingredient_id' => $ingredient->id,
                    'quantity_per_person' => 0,
                    'needs_configuration' => true,
                ]],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('menus', ['name' => 'Fel de configurat']);

        $menu = Menu::query()->where('name', 'Fel de configurat')->firstOrFail();

        $this->assertTrue((bool) ($menu->ingredients[0]['needs_configuration'] ?? false));
        $this->assertArrayNotHasKey('estimated_unit_cost', $menu->ingredients[0]);
    }

    public function test_the_calendar_and_report_pages_load_for_an_administrator(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();

        $this->actingAs($administrator)
            ->get(\App\Filament\Pages\CalendarSaptamanal::getUrl(panel: 'admin'))
            ->assertOk();

        $this->actingAs($administrator)
            ->get(route('weekly-plan-pdfs.show', $week))
            ->assertOk();
    }
}
