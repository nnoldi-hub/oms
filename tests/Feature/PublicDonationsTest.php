<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Support\DonationInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDonationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('oms.donations_contact_name', 'Fratele Test');
        config()->set('oms.donations_contact_phone', '0700000000');
    }

    public function test_the_public_page_shows_the_exact_invitation_and_the_configured_contact(): void
    {
        $this->get(route('public-donations.show'))
            ->assertOk()
            ->assertSee('noindex, nofollow', escape: false)
            ->assertSee(DonationInvitation::render(), escape: false)
            ->assertSee('Dărnicia și harnicia de care dați dovadă sunt cunoscute de toți.', escape: false)
            ->assertSee('gustări dulci și sărate', escape: false)
            ->assertSee('fratele Fratele Test (0700000000).', escape: false)
            ->assertSee('tel:0700000000', escape: false);
    }

    public function test_the_public_page_never_exposes_donor_details(): void
    {
        Donation::factory()->create([
            'donor_name' => 'Donator Privat',
            'donor_phone' => '0711111111',
            'notes' => 'Nota privata',
        ]);

        $this->get(route('public-donations.show'))
            ->assertOk()
            ->assertDontSee('Donator Privat')
            ->assertDontSee('0711111111')
            ->assertDontSee('Nota privata');
    }
}
