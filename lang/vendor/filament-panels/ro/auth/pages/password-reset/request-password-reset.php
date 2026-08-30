<?php

return [

    'title' => 'Reseteaza parola',

    'heading' => 'Ai uitat parola?',

    'actions' => [

        'login' => [
            'label' => 'inapoi la autentificare',
        ],

    ],

    'form' => [

        'email' => [
            'label' => 'Email',
        ],

        'actions' => [

            'request' => [
                'label' => 'Trimite email',
            ],

        ],

    ],

    'notifications' => [

        'sent' => [
            'body' => 'Daca contul dumneavoastra nu exista, nu veti primi emailul.',
        ],

        'throttled' => [
            'title' => 'Prea multe incercari consecutive',
            'body' => 'Va rugam sa incercati din nou in :seconds secunde.',
        ],

    ],

];
