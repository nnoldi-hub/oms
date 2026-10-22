<?php

namespace Tests\Feature;

use App\Filament\Pages\CalendarSaptamanal;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\FoodFundTransaction;
use App\Models\Menu;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\Week;
use App\Services\WeeklyPlanReportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WeeklyPlanPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_pdf_is_generated_for_the_administrator(): void
    {
        $week = $this->weekWithMeals();
        $administrator = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($administrator)
            ->get(route('weekly-plan-pdfs.show', $week));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_the_report_contains_every_day_with_its_roles_and_no_contributions(): void
    {
        $week = $this->weekWithMeals();
        $html = $this->renderReport($week);

        foreach (['Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri'] as $weekday) {
            $this->assertStringContainsString($weekday, $html);
        }

        $this->assertStringContainsString('Mâncare de cartofi', $html);
        $this->assertStringContainsString('Ciorbă de legume', $html);
        $this->assertStringContainsString('Aprovizionare și transport', $html);
        $this->assertStringContainsString('Gătit', $html);
        $this->assertStringContainsString('Împachetare și servire', $html);
        $this->assertStringContainsString('Donații', $html);
        $this->assertStringContainsString('Roluri neacoperite: Gătit', $html);
        $this->assertStringContainsString('Zi liberă', $html);

        // sumele contribuite de persoane nu apar in raport
        $this->assertStringNotContainsString('999.99', $html);
        $this->assertStringNotContainsString('Contributie', $html);
    }

    public function test_the_phone_numbers_disappear_when_the_option_is_disabled(): void
    {
        $week = $this->weekWithMeals();

        $this->assertStringContainsString('0712000111', $this->renderReport($week));
        $this->assertStringContainsString('0712000222', $this->renderReport($week));

        $withoutPhones = $this->renderReport($week, false);

        $this->assertStringNotContainsString('0712000111', $withoutPhones);
        $this->assertStringNotContainsString('0712000222', $withoutPhones);
        $this->assertStringContainsString('Ionuț Popescu', $withoutPhones);
    }

    public function test_only_the_administrator_and_the_coordinator_of_the_week_can_download_it(): void
    {
        $congregation = Congregation::factory()->create(['name' => 'Congregatia Est']);
        $otherCongregation = Congregation::factory()->create(['name' => 'Congregatia Vest']);
        $week = Week::factory()->for($congregation)->create();
        $menu = Menu::factory()->create();
        DailyMeal::factory()->for($week)->for($congregation)->for($menu)->create(['estimated_people' => 20]);

        $coordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $congregation->id]);
        $otherCoordinator = User::factory()->create(['role' => 'coordinator', 'congregation_id' => $otherCongregation->id]);
        $construction = User::factory()->create(['role' => 'construction']);
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)->get(route('weekly-plan-pdfs.show', $week))->assertOk();
        $this->actingAs($coordinator)->get(route('weekly-plan-pdfs.show', $week))->assertOk();
        $this->actingAs($otherCoordinator)->get(route('weekly-plan-pdfs.show', $week))->assertForbidden();
        $this->actingAs($construction)->get(route('weekly-plan-pdfs.show', $week))->assertForbidden();
        $this->get(route('weekly-plan-pdfs.show', $week))->assertForbidden();
    }

    public function test_the_report_keeps_one_page_for_five_days_and_a_second_page_for_seven_days(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);

        $week = $this->weekWithMeals();
        $this->assertSame(1, $this->pageCount($week, $administrator));

        $menu = Menu::where('name', 'Mâncare de cartofi')->first();

        foreach (['2026-12-12', '2026-12-13'] as $date) {
            DailyMeal::factory()->for($week)->for($week->congregation)->for($menu)->create([
                'meal_date' => $date,
                'estimated_people' => 30,
            ]);
        }

        $this->assertSame(2, $this->pageCount($week, $administrator));
    }

    public function test_the_download_button_is_only_shown_to_the_authorized_users(): void
    {
        $week = $this->weekWithMeals();
        $administrator = User::factory()->create(['role' => 'admin']);
        $construction = User::factory()->create(['role' => 'construction']);

        Livewire::actingAs($administrator)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->assertSee('Descarcă PDF planul săptămânii')
            ->call('openPlanPdf')
            ->assertSet('showPlanPdf', true)
            ->assertSee('Include telefoane');

        Livewire::actingAs($construction)
            ->test(CalendarSaptamanal::class)
            ->set('weekId', $week->id)
            ->assertDontSee('Descarcă PDF planul săptămânii');
    }

    public function test_the_days_with_unconfigured_costs_are_marked_as_partial(): void
    {
        $week = $this->weekWithMeals();
        $menuWithoutPrice = Menu::factory()->create([
            'name' => 'Mâncare fără preț',
            'ingredients' => [['name' => 'Pastai', 'quantity_per_person' => 0.2, 'unit' => 'kg']],
            'allergens' => [],
            'packaging_cost' => 0,
        ]);
        $meal = DailyMeal::query()->where('week_id', $week->id)->orderBy('meal_date')->firstOrFail();
        $meal->menu_id = $menuWithoutPrice->id;
        $meal->saveQuietly();

        $html = $this->renderReport($week);

        $this->assertStringContainsString('<span class="day__partial">parțial</span>', $html);
        $this->assertStringContainsString('Cost estimat (parțial)', $html);
        $this->assertStringContainsString('parțial — lipsesc date de cost', $html);
    }

    public function test_the_allergens_section_lists_every_day(): void
    {
        $week = $this->weekWithMeals();
        $menuWithoutAllergens = Menu::factory()->create([
            'name' => 'Prânz fără alergeni',
            'ingredients' => [['name' => 'Orez', 'quantity_per_person' => 0.2, 'unit' => 'kg', 'estimated_unit_cost' => 5]],
            'allergens' => [],
            'packaging_cost' => 0,
        ]);
        $meal = DailyMeal::query()->where('week_id', $week->id)->orderBy('meal_date')->skip(2)->firstOrFail();
        $meal->menu_id = $menuWithoutAllergens->id;
        $meal->soup_menu_id = null;
        $meal->dessert_menu_id = null;
        $meal->saveQuietly();

        $report = app(WeeklyPlanReportBuilder::class)->build($week);

        $this->assertCount(5, $report['allergens']);
        $this->assertSame('Telina', $report['allergens'][0]['text']);
        $this->assertSame('nedeclarat – de verificat', $report['allergens'][2]['text']);

        $html = $this->renderReport($week);

        $this->assertStringContainsString('Alergeni pe zi', $html);
        $this->assertStringContainsString('Telina', $html);
        $this->assertStringContainsString('nedeclarat – de verificat', $html);
    }

    public function test_the_period_covers_the_first_and_the_last_scheduled_meal(): void
    {
        $week = Week::factory()->create(['week_number' => 51, 'start_date' => '2026-12-07']);
        $menu = Menu::factory()->create([
            'ingredients' => [['name' => 'Cartofi', 'quantity_per_person' => 0.2, 'unit' => 'kg', 'estimated_unit_cost' => 5]],
            'packaging_cost' => 1,
        ]);

        DailyMeal::factory()->for($week)->for($menu)->create(['meal_date' => '2026-12-09', 'estimated_people' => 10]);
        DailyMeal::factory()->for($week)->for($menu)->create(['meal_date' => '2026-12-11', 'estimated_people' => 10]);

        $report = app(WeeklyPlanReportBuilder::class)->build($week);

        $this->assertSame('9 decembrie 2026 – 11 decembrie 2026', $report['range']);
        $this->assertStringContainsString('9 decembrie 2026 – 11 decembrie 2026', $this->renderReport($week));
    }

    private function weekWithMeals(int $days = 5): Week
    {
        $congregation = Congregation::factory()->create([
            'name' => 'Congregatia Est '.$days,
            'assistant_name' => 'Maria Ionescu',
            'assistant_phone' => '0712000222',
            'assistant_email' => 'maria@example.com',
        ]);

        $week = Week::factory()->for($congregation)->create([
            'week_number' => 42 + $days,
            'start_date' => '2026-12-07',
        ]);

        $mainMenu = Menu::factory()->create([
            'name' => 'Mâncare de cartofi',
            'ingredients' => [['name' => 'Cartofi', 'quantity_per_person' => 0.2, 'unit' => 'kg', 'estimated_unit_cost' => 5]],
            'allergens' => ['Telina'],
            'packaging_cost' => 1,
        ]);
        $soupMenu = Menu::factory()->create([
            'name' => 'Ciorbă de legume',
            'type' => 'soup',
            'ingredients' => [['name' => 'Morcovi', 'quantity_per_person' => 0.1, 'unit' => 'kg', 'estimated_unit_cost' => 1]],
            'packaging_cost' => 0,
        ]);
        $dessertMenu = Menu::factory()->create([
            'name' => 'Napolitane',
            'type' => 'dessert',
            'ingredients' => [['name' => 'Napolitana', 'quantity_per_person' => 1, 'unit' => 'buc', 'estimated_unit_cost' => 2]],
            'packaging_cost' => 0,
        ]);

        for ($index = 0; $index < $days; $index++) {
            $date = '2026-12-'.str_pad((string) (7 + $index), 2, '0', STR_PAD_LEFT);

            $meal = DailyMeal::factory()->for($week)->for($congregation)->for($mainMenu)->create([
                'meal_date' => $date,
                'estimated_people' => 30,
                'confirmed_people' => $index === 0 ? 40 : null,
                'soup_menu_id' => $index === 1 ? $soupMenu->id : null,
                'dessert_menu_id' => $index === 2 ? $dessertMenu->id : null,
                'is_free_day' => $index === 3,
            ]);

            Volunteer::factory()->for($meal)->create([
                'name' => 'Ionuț Popescu',
                'phone' => '0712000111',
                'role' => 'Aprovizionare si transport',
                'roles' => ['Aprovizionare si transport'],
            ]);

            if ($index === 0) {
                Volunteer::factory()->for($meal)->create([
                    'name' => 'Ana Georgeta',
                    'phone' => '0712000333',
                    'role' => 'Donatii',
                    'roles' => ['Donatii'],
                ]);
            }

            FoodFundTransaction::create([
                'type' => 'income',
                'transaction_date' => $date,
                'amount' => 999.99,
                'category' => 'volunteer_contribution',
                'counterparty' => 'Ionuț Popescu',
                'description' => 'Contributie voluntar',
                'daily_meal_id' => $meal->id,
                'volunteer_contribution_status' => 'paid',
            ]);
        }

        return $week;
    }

    private function renderReport(Week $week, bool $includePhones = true): string
    {
        $report = app(WeeklyPlanReportBuilder::class)->build($week, $includePhones);

        return view('weekly-plan-report', ['report' => $report])->render();
    }

    private function pageCount(Week $week, User $user): int
    {
        $response = $this->actingAs($user)->get(route('weekly-plan-pdfs.show', $week));
        $response->assertOk();

        return preg_match_all('/\/Type\s*\/Page[^s]/', $response->getContent());
    }
}
