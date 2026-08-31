<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Week;
use Database\Seeders\MenuCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_the_catalog_without_changing_existing_schedule_data(): void
    {
        $congregation = Congregation::factory()->create();
        $menu = Menu::factory()->create(['type' => 'main']);
        $meal = DailyMeal::factory()->for(Week::factory()->for($congregation))->for($congregation)->for($menu)->create([
            'estimated_people' => 47,
            'status' => 'confirmed',
        ]);

        $this->seed(MenuCatalogSeeder::class);

        $this->assertDatabaseCount('menus', 23);
        $this->assertSame(13, Menu::where('type', 'main')->where('is_active', true)->count());
        $this->assertSame(4, Menu::where('type', 'soup')->where('is_active', true)->count());
        $this->assertSame(6, Menu::where('type', 'dessert')->where('is_active', true)->count());
        $this->assertSame(47, $meal->fresh()->estimated_people);
        $this->assertSame('confirmed', $meal->fresh()->status);
        $this->assertSame($menu->id, $meal->fresh()->menu_id);
        $this->assertSame(22, $congregation->fresh()->menus()->count());
    }
}