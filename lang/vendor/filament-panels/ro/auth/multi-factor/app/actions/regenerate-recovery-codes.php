<?php

return [

    'label' => 'Regenereaza codurile de recuperare',

    'modal' => [

        'heading' => 'Regenerare coduri de recuperare pentru aplicatia de autentificare',

        'description' => 'Daca ati pierdut codurile de recuperare, le puteti regenera aici. Codurile de recuperare vechi vor fi invalidate imediat.',

        'form' => [

            'code' => [

                'label' => 'Introduceti codul din 6 cifre din aplicatia de autentificare',

                'validation_attribute' => 'cod',

                'messages' => [

                    'invalid' => 'Codul introdus este invalid.',

                ],

            ],

            'password' => [

                'label' => 'Sau, introduceti parola curenta',

                'validation_attribute' => 'parola',

            ],

        ],

        'actions' => [

            'submit' => [
                'label' => 'Regenereaza codurile de recuperare',
            ],

        ],

    ],

    'notifications' => [

        'regenerated' => [
            'title' => 'Noi coduri de recuperare au fost generate',
        ],

    ],

    'show_new_recovery_codes' => [

        'modal' => [

            'heading' => 'Coduri de recuperare noi',

            'description' => 'Va rugam sa salvati urmatoarele coduri de recuperare intr-un loc sigur. Acestea vor fi afisate doar o singura data, dar veti avea nevoie de ele daca pierdeti accesul la aplicatia de autentificare:',

            'actions' => [

                'submit' => [
                    'label' => 'Inchide',
                ],

            ],

        ],

    ],

];
