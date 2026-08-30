<?php

return [

    'label' => 'Configureaza',

    'modal' => [

        'heading' => 'Configurare aplicatie de autentificare',

        'description' => <<<'BLADE'
            Veti avea nevoie de o aplicatie precum Google Authenticator (<x-filament::link href="https://itunes.apple.com/us/app/google-authenticator/id388497605" target="_blank">iOS</x-filament::link>, <x-filament::link href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Android</x-filament::link>) pentru a finaliza acest proces.
            BLADE,

        'content' => [

            'qr_code' => [

                'instruction' => 'Scanati acest cod QR cu aplicatia de autentificare:',

                'alt' => 'Cod QR de scanat cu o aplicatie de autentificare',

            ],

            'text_code' => [

                'instruction' => 'Sau introduceti acest cod manual:',

                'messages' => [
                    'copied' => 'Copiat',
                ],

            ],

            'recovery_codes' => [

                'instruction' => 'Va rugam sa salvati urmatoarele coduri de recuperare intr-un loc sigur. Acestea vor fi afisate doar o singura data, dar veti avea nevoie de ele daca pierdeti accesul la aplicatia de autentificare:',

            ],

        ],

        'form' => [

            'code' => [

                'label' => 'Introduceti codul din 6 cifre din aplicatia de autentificare',

                'validation_attribute' => 'cod',

                'below_content' => 'Va trebui sa introduceti codul din 6 cifre din aplicatia de autentificare de fiecare data cand va autentificati sau efectuati actiuni sensibile.',

                'messages' => [

                    'invalid' => 'Codul introdus este invalid.',

                ],

            ],

        ],

        'actions' => [

            'submit' => [
                'label' => 'Activeaza aplicatia de autentificare',
            ],

        ],

    ],

    'notifications' => [

        'enabled' => [
            'title' => 'Aplicatia de autentificare a fost activata',
        ],

    ],

];
