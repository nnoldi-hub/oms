<?php

return [

    'title' => 'Confirma adresa de email',

    'heading' => 'Confirma adresa de email',

    'actions' => [

        'resend_notification' => [
            'label' => 'Retrimite',
        ],

    ],

    'messages' => [
        'notification_not_received' => 'Nu ai primit emailul de verificare?',
        'notification_sent' => 'S-a trimis un email la :email cu instructiuni pentru a confirma adresa de email.',
    ],

    'notifications' => [

        'notification_resent' => [
            'title' => 'Am retrimis emailul.',
        ],

        'notification_resend_throttled' => [
            'title' => 'Prea multe incercari consecutive de retrimitere',
            'body' => 'Incearca te rog din nou peste :seconds secunde.',
        ],

    ],

];
