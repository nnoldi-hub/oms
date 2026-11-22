<?php

namespace Tests\Feature;

use App\Filament\Resources\Congregations\Pages\ListCongregations;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\User;
use App\Models\Week;
use App\Services\ContactImportService;
use App\Services\WeeklyPlanReportBuilder;
use App\Support\CongregationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ContactImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_dry_run_parse_reports_changes_without_writing_them(): void
    {
        $congregation = Congregation::factory()->create([
            'name' => 'Congregatia Alpha',
            'type' => CongregationRole::CONTRIBUTES,
            'assistant_name' => 'Contact Vechi',
            'assistant_phone' => '0712000001',
            'assistant_email' => 'vechi@example.test',
        ]);

        $service = app(ContactImportService::class);

        $preview = $service->parse("nume congregatie,persoana,telefon,email\nCongregatia Alpha,Contact Nou,0712000002,nou@example.test\nCongregatia Lipsa,Nimeni,0712000003,nimeni@example.test\n");

        $this->assertSame(2, $preview['total']);
        $this->assertSame(1, $preview['matched']);
        $this->assertSame(1, $preview['changed']);
        $this->assertSame(['Congregatia Lipsa'], $preview['missing']);
        $this->assertStringContainsString('1 cu modificari', $service->summary($preview));

        $congregation->refresh();

        $this->assertSame('Contact Vechi', $congregation->assistant_name);
        $this->assertSame('0712000001', $congregation->assistant_phone);
        $this->assertSame('vechi@example.test', $congregation->assistant_email);
    }

    public function test_apply_updates_only_matched_congregations(): void
    {
        $first = Congregation::factory()->create(['name' => 'Congregatia Alpha']);
        $second = Congregation::factory()->create(['name' => 'Congregatia Beta']);

        $service = app(ContactImportService::class);
        $preview = $service->parse("Congregatia Alpha;Persoana Alpha;0712000011;alpha@example.test\nCongregatia Beta;Persoana Beta;0712000012;beta@example.test\n");

        $this->assertSame(2, $service->apply($preview));

        $this->assertSame('Persoana Alpha', $first->fresh()->assistant_name);
        $this->assertSame('0712000011', $first->fresh()->assistant_phone);
        $this->assertSame('Persoana Beta', $second->fresh()->assistant_name);
        $this->assertSame('0712000012', $second->fresh()->assistant_phone);
    }

    public function test_admins_can_quick_edit_phones_and_the_panel_import_keeps_dry_run_as_default(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $congregation = Congregation::factory()->create(['name' => 'Congregatia Alpha']);

        $this->actingAs($admin);

        Livewire::test(ListCongregations::class)
            ->assertTableActionVisible('editPhone', $congregation)
            ->assertTableActionVisible('importContacts')
            ->callTableAction('editPhone', $congregation, ['assistant_phone' => '0712000099']);

        $this->assertSame('0712000099', $congregation->fresh()->assistant_phone);

        Storage::disk('local')->put('contact-imports/test.csv', "nume congregatie,persoana,telefon,email\nCongregatia Alpha,Contact Nou,0712000088,nou@example.test\n");

        Livewire::test(ListCongregations::class)
            ->callTableAction('importContacts', null, [
                'csv' => ['contact-imports/test.csv'],
                'confirm' => false,
            ]);

        $this->assertSame('0712000099', $congregation->fresh()->assistant_phone);

        Livewire::test(ListCongregations::class)
            ->callTableAction('importContacts', null, [
                'csv' => ['contact-imports/test.csv'],
                'confirm' => true,
            ]);

        $this->assertSame('0712000088', $congregation->fresh()->assistant_phone);
        $this->assertSame('Contact Nou', $congregation->fresh()->assistant_name);

        Storage::disk('local')->delete('contact-imports/test.csv');
    }

    public function test_congregation_contacts_can_be_excluded_from_the_weekly_pdf_report(): void
    {
        $cooks = Congregation::factory()->create([
            'name' => 'Bucuresti Est',
            'type' => CongregationRole::COOKS,
            'assistant_name' => 'Contact Bucuresti Est',
            'assistant_phone' => '0712000077',
            'assistant_email' => 'est@example.test',
        ]);
        $week = Week::factory()->for($cooks)->create();
        DailyMeal::factory()->for($week)->for($cooks)->create();

        $builder = app(WeeklyPlanReportBuilder::class);

        $this->assertSame('0712000077', $builder->build($week, true, true)['contacts'][0]['phone']);
        $this->assertSame([], $builder->build($week, true, false)['contacts']);
    }

    public function test_coordinators_cannot_see_contact_actions_or_columns(): void
    {
        $congregation = Congregation::factory()->create(['name' => 'Congregatia Alpha']);
        $coordinator = User::factory()->create([
            'role' => 'coordinator',
            'congregation_id' => $congregation->id,
        ]);

        $this->actingAs($coordinator);

        Livewire::test(ListCongregations::class)
            ->assertTableActionHidden('editPhone', $congregation)
            ->assertTableActionHidden('importContacts')
            ->assertTableColumnHidden('assistant_phone');
    }
}
