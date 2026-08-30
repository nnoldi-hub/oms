<?php

return [

    'label' => 'Profil',

    'form' => [

        'email' => [
            'label' => 'Adresa de email',
        ],

        'name' => [
            'label' => 'Nume',
        ],

        'password' => [
            'label' => 'Parola noua',
            'validation_attribute' => 'parola',
        ],

        'password_confirmation' => [
            'label' => 'Confirma parola noua',
            'validation_attribute' => 'confirmare parola',
        ],

        'current_password' => [
            'label' => 'Parola curenta',
            'below_content' => 'Pentru securitate, va rugam sa confirmati parola pentru a continua.',
            'validation_attribute' => 'parola curenta',
        ],

        'actions' => [

            'save' => [
                'label' => 'Salveaza modificarile',
            ],

        ],

    ],

    'multi_factor_authentication' => [
        'label' => 'Autentificare cu doi factori (2FA)',
    ],

    'notifications' => [

        'email_change_verification_sent' => [
            'title' => 'Cerere de schimbare a adresei de email trimisa',
            'body' => 'O cerere de schimbare a adresei de email a fost trimisa la :email. Va rugam sa verificati emailul pentru a confirma schimbarea.',
        ],

        'saved' => [
            'title' => 'Salvat cu succes',
        ],

    ],

    'actions' => [

        'cancel' => [
            'label' => 'Anulare',
        ],

    ],

];
