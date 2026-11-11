<?php

namespace Tests\Feature;

use App\Filament\Pages\GhidUtilizare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GhidUtilizareTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_help_guide_is_available_to_every_role(): void
    {
        foreach (['admin', 'coordinator', 'construction', 'kitchen'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(GhidUtilizare::getUrl(panel: 'admin'))
                ->assertOk()
                ->assertSee('Ghid de utilizare');
        }
    }

    public function test_the_help_guide_explains_the_start_date_and_the_congregation_rotation(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)
            ->get(GhidUtilizare::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSee('Data de inceput a programului')
            ->assertSee('oms:shift-weekdays')
            ->assertSee('Genereaza planificare')
            ->assertSee('automata la generare')
            ->assertSee('2 zile / 2 zile / 1 zi')
            ->assertSee('Reatribuire manuala');
    }

    public function test_the_help_guide_explains_the_menu_usage_notes_and_warnings(): void
    {
        $coordinator = User::factory()->create(['role' => 'coordinator']);

        $this->actingAs($coordinator)
            ->get(GhidUtilizare::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSee('Gatit de N ori in ultimele 6 saptamani')
            ->assertSee('ultimele 14 zile')
            ->assertSee('minimum 10 zile')
            ->assertSee('nu blocheaza salvarea')
            ->assertSee('oms:fill-menus');
    }
}
