<?php

namespace Tests\Feature;

use App\Filament\Resources\Menus\MenuResource;
use App\Models\Congregation;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatorRecipeCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_coordinator_can_browse_the_recipe_catalog_without_editing_it(): void
    {
        $menu = Menu::factory()->create(['name' => 'Ciorba de perisoare', 'type' => 'soup']);
        $coordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => Congregation::factory()->create()->id,
        ]);

        $this->actingAs($coordinator);

        $this->get('/admin/menus?type=soup')->assertOk()->assertSee('Ciorba de perisoare');
        $this->get('/admin/menus?type=main')->assertOk();
        $this->get(MenuResource::getUrl('view', ['record' => $menu]))->assertOk();
        $this->get(MenuResource::getUrl('edit', ['record' => $menu]))->assertForbidden();
        $this->get(MenuResource::getUrl('create'))->assertForbidden();
    }

    public function test_the_kitchen_links_are_hidden_from_roles_without_access(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'construction']));

        $this->get('/admin')->assertOk()->assertDontSee('/admin/menus?type=soup', false);
    }
}
