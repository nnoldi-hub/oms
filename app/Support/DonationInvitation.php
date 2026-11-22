<?php

namespace App\Support;

/**
 * Textul unic al invitatiei la donatii, folosit pe pagina publica si in
 * mesajele WhatsApp. Contactul este luat exclusiv din configuratie.
 */
final class DonationInvitation
{
    public const TEMPLATE = 'Dărnicia și harnicia de care dați dovadă sunt cunoscute de toți. Generozitatea de care veți da dovadă, oferind donații sub formă de alimente (legume, fructe, carne), precum și gustări dulci și sărate, va fi de un real ajutor. Pentru aceste donații îl puteți contacta pe fratele {nume} ({telefon}).';

    public static function contactName(): string
    {
        return trim((string) config('oms.donations_contact_name', ''));
    }

    public static function contactPhone(): string
    {
        return trim((string) config('oms.donations_contact_phone', ''));
    }

    public static function render(): string
    {
        return str_replace(
            ['{nume}', '{telefon}'],
            [self::contactName(), self::contactPhone()],
            self::TEMPLATE,
        );
    }
}
