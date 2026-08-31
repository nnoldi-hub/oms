<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuCategoryNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_kitchen_user_sees_only_the_selected_recipe_category(): void
    {
        Menu::factory()->create(['name' => 'Fel principal test', 'type' => 'main']);
        Menu::factory()->create(['name' => 'Ciorba test', 'type' => 'soup']);
        Menu::factory()->create(['name' => 'Desert test', 'type' => 'dessert']);

        $this->actingAs(User::factory()->create(['role' => 'kitchen']))
            ->get('/admin/menus?type=dessert')
            ->assertOk()
            ->assertSee('Desert test')
            ->assertDontSee('Fel principal test')
            ->assertDontSee('Ciorba test');
    }
}