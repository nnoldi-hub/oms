<?php

namespace App\Http\Controllers;

use App\Support\DonationInvitation;
use Illuminate\Contracts\View\View;

class PublicDonationsController extends Controller
{
    public function __invoke(): View
    {
        return view('public-donations', [
            'invitation' => DonationInvitation::render(),
            'contactName' => DonationInvitation::contactName(),
            'contactPhone' => DonationInvitation::contactPhone(),
        ]);
    }
}
