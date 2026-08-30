<?php

return [

    'title' => 'Inregistrare',

    'heading' => 'Creeaza cont',

    'actions' => [

        'login' => [
            'before' => 'sau',
            'label' => 'logheaza-te in contul tau',
        ],

    ],

    'form' => [

        'email' => [
            'label' => 'Email',
        ],

        'name' => [
            'label' => 'Nume',
        ],

        'password' => [
            'label' => 'Parola',
            'validation_attribute' => 'password',
        ],

        'password_confirmation' => [
            'label' => 'Confirma parola',
        ],

        'actions' => [

            'register' => [
                'label' => 'Creeaza cont',
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
