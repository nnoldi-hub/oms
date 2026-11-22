<?php

namespace Tests\Feature;

use App\Models\Congregation;
use App\Support\CongregationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookingCongregationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_congregations_are_contributors_by_default(): void
    {
        $congregation = Congregation::factory()->create();

        $this->assertSame(CongregationRole::CONTRIBUTES, $congregation->type);
    }

    public function test_the_command_sets_cooking_congregations_by_name_only_when_applied(): void
    {
        $east = Congregation::factory()->create(['name' => 'Bucuresti Est']);
        $titan = Congregation::factory()->create(['name' => 'Bucuresti Titan']);
        $contributor = Congregation::factory()->create(['name' => 'Congregatia Contribuitoare']);

        $this->artisan('oms:set-cooking-congregations', [
            'names' => [$east->name, $titan->name],
        ])
            ->expectsOutputToContain('Mod de testare (implicit): nu s-a modificat nimic.')
            ->assertSuccessful();

        $this->assertSame(CongregationRole::CONTRIBUTES, $east->fresh()->type);

        $this->artisan('oms:set-cooking-congregations', [
            'names' => [$east->name, $titan->name],
            '--apply' => true,
        ])
            ->expectsOutputToContain('Actualizat: 2 congregatii devin gateste')
            ->assertSuccessful();

        $this->assertSame(CongregationRole::COOKS, $east->fresh()->type);
        $this->assertSame(CongregationRole::COOKS, $titan->fresh()->type);
        $this->assertSame(CongregationRole::CONTRIBUTES, $contributor->fresh()->type);

        $this->artisan('oms:set-cooking-congregations', [
            'names' => [$east->name, $titan->name],
            '--apply' => true,
        ])
            ->expectsOutputToContain('Actualizat: 0 congregatii devin gateste, 0 devin contribuie.')
            ->assertSuccessful();
    }

    public function test_the_command_demotes_previous_cooking_congregations_not_in_the_list(): void
    {
        $previous = Congregation::factory()->create([
            'name' => 'Fosta Gateste',
            'type' => CongregationRole::COOKS,
        ]);
        $next = Congregation::factory()->create(['name' => 'Noua Gateste']);

        $this->artisan('oms:set-cooking-congregations', [
            'names' => [$next->name],
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertSame(CongregationRole::CONTRIBUTES, $previous->fresh()->type);
        $this->assertSame(CongregationRole::COOKS, $next->fresh()->type);
    }

    public function test_the_command_rejects_unknown_names_without_writing_anything(): void
    {
        $congregation = Congregation::factory()->create(['name' => 'Congregatia Cunoscuta']);

        $this->artisan('oms:set-cooking-congregations', [
            'names' => [$congregation->name, 'Congregatia Inexistenta'],
            '--apply' => true,
        ])
            ->expectsOutputToContain('Congregatia Inexistenta')
            ->assertFailed();

        $this->assertSame(CongregationRole::CONTRIBUTES, $congregation->fresh()->type);
    }
}
