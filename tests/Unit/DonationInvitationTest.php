<?php

namespace Tests\Unit;

use App\Models\DailyMeal;
use App\Models\Menu;
use App\Services\DailyMessageBuilder;
use App\Support\DonationInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('oms.donations_contact_name', 'Fratele Test');
        config()->set('oms.donations_contact_phone', '0700000000');
    }

    public function test_the_rendered_invitation_replaces_both_placeholders(): void
    {
        $rendered = DonationInvitation::render();

        $this->assertStringContainsString('oferind donații sub formă de alimente (legume, fructe, carne)', $rendered);
        $this->assertStringContainsString('fratele Fratele Test (0700000000).', $rendered);
        $this->assertStringNotContainsString('{nume}', $rendered);
        $this->assertStringNotContainsString('{telefon}', $rendered);
    }

    public function test_the_day_message_contains_the_invitation_exactly_once(): void
    {
        $meal = DailyMeal::factory()->for(Menu::factory())->create();

        $message = app(DailyMessageBuilder::class)->dayMessage($meal);

        $this->assertSame(1, substr_count($message, DonationInvitation::render()));
    }

    public function test_the_week_message_contains_the_invitation_exactly_once(): void
    {
        $meals = DailyMeal::factory()->count(3)->for(Menu::factory())->create();

        $message = app(DailyMessageBuilder::class)->weekMessage($meals, 7, '14.12 - 20.12');

        $this->assertStringContainsString('Planul saptamanii 7', $message);
        $this->assertSame(1, substr_count($message, DonationInvitation::render()));
    }
}
