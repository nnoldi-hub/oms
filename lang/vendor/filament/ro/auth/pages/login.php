<?php

return [

    'title' => 'Autentificare',

    'heading' => 'Logheaza-te in contul tau',

    'actions' => [

        'register' => [
            'before' => 'sau',
            'label' => 'creeaza cont',
        ],

        'request_password_reset' => [
            'label' => 'Ai uitat parola?',
        ],

    ],

    'form' => [

        'email' => [
            'label' => 'Email',
        ],

        'password' => [
            'label' => 'Parola',
        ],

        'remember' => [
            'label' => 'Tine-ma minte',
        ],

        'actions' => [

            'authenticate' => [
                'label' => 'Autentificare',
            ],

        ],

    ],

    'multi_factor' => [

        'heading' => 'Verificati-va identitatea',

        'subheading' => 'Pentru a continua autentificarea, trebuie sa va verificati identitatea.',

        'form' => [

            'provider' => [
                'label' => 'Cum doriti sa verificati?',
            ],

            'actions' => [

                'authenticate' => [
                    'label' => 'Confirma autentificarea',
                ],

            ],

        ],

    ],

    'messages' => [

        'failed' => 'Emailul sau parola nu sunt corecte.',

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Prea multe incercari de autentificare',
            'body' => 'Va rugam sa incercati din nou in :seconds secunde.',
        ],

    ],

];
