<?php

return [

    'management_schema' => [

        'actions' => [

            'label' => 'Aplicatie de autentificare',

            'below_content' => 'Folositi o aplicatie securizata pentru a genera un cod temporar pentru verificarea autentificarii.',

            'messages' => [
                'enabled' => 'Activat',
                'disabled' => 'Dezactivat',
            ],

        ],

    ],

    'login_form' => [

        'label' => 'Foloseste un cod din aplicatia de autentificare',

        'code' => [

            'label' => 'Introduceti codul din 6 cifre din aplicatia de autentificare',

            'validation_attribute' => 'cod',

            'actions' => [

                'use_recovery_code' => [
                    'label' => 'Foloseste in schimb un cod de recuperare',
                ],

            ],

            'messages' => [

                'invalid' => 'Codul introdus este invalid.',

            ],

        ],

        'recovery_code' => [

            'label' => 'Sau, introduceti un cod de recuperare',

            'validation_attribute' => 'cod de recuperare',

            'messages' => [

                'invalid' => 'Codul de recuperare introdus este invalid.',

            ],

        ],

    ],

];
