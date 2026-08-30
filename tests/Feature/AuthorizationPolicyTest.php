<?php

namespace Tests\Feature;

use App\Filament\Resources\Congregations\CongregationResource;
use App\Filament\Resources\DailyMeals\DailyMealResource;
use App\Filament\Resources\Volunteers\VolunteerResource;
use App\Filament\Resources\Weeks\WeekResource;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\Week;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_coordinator_can_only_access_records_from_their_congregation(): void
    {
        $ownCongregation = Congregation::factory()->create();
        $otherCongregation = Congregation::factory()->create();
        $coordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $ownCongregation->id,
        ]);
        $ownWeek = Week::factory()->for($ownCongregation)->create();
        $otherWeek = Week::factory()->for($otherCongregation)->create();
        $ownVolunteer = Volunteer::factory()->for(DailyMeal::factory()->for($ownWeek)->for($ownCongregation))->create();
        $otherVolunteer = Volunteer::factory()->for(DailyMeal::factory()->for($otherWeek)->for($otherCongregation))->create();

        $this->assertTrue($coordinator->can('view', $ownWeek));
        $this->assertFalse($coordinator->can('view', $otherWeek));
        $this->assertTrue($coordinator->can('update', $ownVolunteer));
        $this->assertFalse($coordinator->can('update', $otherVolunteer));
    }

    public function test_the_construction_team_can_update_meal_counts_but_cannot_create_meals(): void
    {
        $constructionUser = User::factory()->create(['role' => 'construction']);
        $dailyMeal = DailyMeal::factory()->create();

        $this->assertTrue($constructionUser->can('update', $dailyMeal));
        $this->assertFalse($constructionUser->can('create', DailyMeal::class));
    }

    public function test_the_construction_team_cannot_change_meal_details_on_the_server(): void
    {
        $constructionUser = User::factory()->create(['role' => 'construction']);
        $dailyMeal = DailyMeal::factory()->create(['notes' => 'Nota initiala', 'soup_menu_id' => null]);

        $this->actingAs($constructionUser);
        $dailyMeal->soup_menu_id = 1;

        $this->expectException(AuthorizationException::class);

        $dailyMeal->save();
    }

    public function test_filament_queries_only_return_a_coordinators_congregation_records(): void
    {
        $ownCongregation = Congregation::factory()->create();
        $otherCongregation = Congregation::factory()->create();
        $coordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $ownCongregation->id,
        ]);
        $ownWeek = Week::factory()->for($ownCongregation)->create();
        $otherWeek = Week::factory()->for($otherCongregation)->create();
        $ownMeal = DailyMeal::factory()->for($ownWeek)->for($ownCongregation)->create();
        $otherMeal = DailyMeal::factory()->for($otherWeek)->for($otherCongregation)->create();
        $ownVolunteer = Volunteer::factory()->for($ownMeal)->create();
        $otherVolunteer = Volunteer::factory()->for($otherMeal)->create();

        $this->actingAs($coordinator);

        $this->assertSame([$ownCongregation->id], CongregationResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$ownWeek->id], WeekResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$ownMeal->id], DailyMealResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$ownVolunteer->id], VolunteerResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotSame($ownMeal->id, $otherMeal->id);
        $this->assertNotSame($ownVolunteer->id, $otherVolunteer->id);
    }

    public function test_only_an_administrator_can_manage_user_accounts(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $coordinator = User::factory()->create(['role' => 'coordinator']);
        $managedUser = User::factory()->create(['role' => 'construction']);

        $this->assertTrue($administrator->can('update', $managedUser));
        $this->assertFalse($coordinator->can('viewAny', User::class));
        $this->assertFalse($administrator->can('delete', $administrator));
    }
}