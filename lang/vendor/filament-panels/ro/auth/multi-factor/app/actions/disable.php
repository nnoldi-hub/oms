<?php

return [

    'label' => 'Dezactiveaza',

    'modal' => [

        'heading' => 'Dezactivare aplicatie de autentificare',

        'description' => 'Sigur doriti sa nu mai folositi aplicatia de autentificare? Dezactivarea acesteia va elimina un nivel suplimentar de securitate din contul dumneavoastra.',

        'form' => [

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

        'actions' => [

            'submit' => [
                'label' => 'Dezactiveaza aplicatia de autentificare',
            ],

        ],

    ],

    'notifications' => [

        'disabled' => [
            'title' => 'Aplicatia de autentificare a fost dezactivata',
        ],

    ],

];
