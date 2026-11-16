<?php

namespace Tests\Feature;

use App\Filament\Pages\ReteteDeVerificat;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\MealChangeLog;
use App\Models\Menu;
use App\Models\User;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CoordinatorRecipeReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_recipe_created_by_a_coordinator_is_marked_for_review_and_notifies_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $congregation = Congregation::factory()->create();
        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);

        Livewire::actingAs($coordinator)
            ->test(CreateMenu::class)
            ->fillForm([
                'name' => 'Sarmale noi',
                'type' => 'main',
                'ingredients' => [],
                'packaging_cost' => 0,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $menu = Menu::query()->where('name', 'Sarmale noi')->firstOrFail();

        $this->assertFalse($menu->is_reviewed);
        $this->assertSame($congregation->id, $menu->created_by_congregation_id);
        $this->assertTrue($menu->isNewToReview());

        $this->assertDatabaseHas('meal_change_logs', [
            'event_type' => MealChangeLog::EVENT_RECIPE_CREATED,
            'congregation_id' => $congregation->id,
            'user_id' => $coordinator->id,
        ]);

        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $admin->id)->count());
    }

    public function test_a_recipe_created_by_an_administrator_is_reviewed_by_default(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateMenu::class)
            ->fillForm([
                'name' => 'Reteta admin',
                'type' => 'soup',
                'ingredients' => [],
                'packaging_cost' => 0,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $menu = Menu::query()->where('name', 'Reteta admin')->firstOrFail();

        $this->assertTrue($menu->is_reviewed);
        $this->assertNull($menu->created_by_congregation_id);
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_the_administrator_can_approve_a_recipe_proposed_by_a_coordinator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $congregation = Congregation::factory()->create();
        $menu = Menu::factory()->create([
            'type' => 'main',
            'is_reviewed' => false,
            'created_by_congregation_id' => $congregation->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ReteteDeVerificat::class)
            ->assertSee($menu->name)
            ->call('approve', $menu->id)
            ->assertHasNoErrors();

        $this->assertTrue($menu->fresh()->is_reviewed);
    }

    public function test_the_administrator_can_merge_a_proposed_recipe_into_an_existing_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $congregation = Congregation::factory()->create();
        $week = Week::factory()->for($congregation)->create();
        $target = Menu::factory()->create(['name' => 'Mancare clasica', 'type' => 'main']);
        $source = Menu::factory()->create([
            'name' => 'Mancare noua',
            'type' => 'main',
            'is_reviewed' => false,
            'created_by_congregation_id' => $congregation->id,
        ]);
        $dailyMeal = DailyMeal::factory()->for($week)->for($congregation)->create([
            'menu_id' => $source->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ReteteDeVerificat::class)
            ->call('openMerge', $source->id)
            ->set('mergeTargetId', $target->id)
            ->call('merge')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('menus', ['id' => $source->id]);
        $this->assertSame($target->id, $dailyMeal->fresh()->menu_id);
    }

    public function test_a_coordinator_cannot_access_the_review_page(): void
    {
        $coordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => Congregation::factory()->create()->id,
        ]);

        $this->actingAs($coordinator)
            ->get(ReteteDeVerificat::getUrl(panel: 'admin'))
            ->assertForbidden();
    }
}
