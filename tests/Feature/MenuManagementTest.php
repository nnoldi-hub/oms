<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_kitchen_user_can_manage_menus_but_a_coordinator_cannot(): void
    {
        $menu = Menu::factory()->create();
        $kitchenUser = User::factory()->create(['role' => 'kitchen']);
        $coordinator = User::factory()->create(['role' => 'coordinator']);

        $this->assertTrue($kitchenUser->can('update', $menu));
        $this->assertFalse($coordinator->can('update', $menu));
    }

    public function test_a_menu_rejects_an_ingredient_without_a_positive_quantity_and_valid_unit(): void
    {
        $this->expectException(ValidationException::class);

        Menu::factory()->create([
            'ingredients' => [[
                'name' => 'Orez',
                'quantity_per_person' => 0,
                'unit' => 'cutie',
            ]],
        ]);
    }

    public function test_a_menu_rejects_allergens_outside_the_controlled_list(): void
    {
        $this->expectException(ValidationException::class);

        Menu::factory()->create(['allergens' => ['Necunoscut']]);
    }
}