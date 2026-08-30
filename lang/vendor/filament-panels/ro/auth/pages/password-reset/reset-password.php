<?php

return [

    'title' => 'Reseteaza parola',

    'heading' => 'Reseteaza parola',

    'form' => [

        'email' => [
            'label' => 'Email',
        ],

        'password' => [
            'label' => 'Parola',
            'validation_attribute' => 'password',
        ],

        'password_confirmation' => [
            'label' => 'Confirma parola',
        ],

        'actions' => [

            'reset' => [
                'label' => 'Reseteaza parola',
            ],

        ],

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'Prea multe incercari consecutive',
            'body' => 'Incearca te rog din nou peste :seconds secunde.',
        ],

    ],

];
