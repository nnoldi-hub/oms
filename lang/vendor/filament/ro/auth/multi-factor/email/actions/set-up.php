<?php

return [

    'label' => 'Configureaza',

    'modal' => [

        'heading' => 'Configurare coduri de verificare prin email',

        'description' => 'Va trebui sa introduceti codul din 6 cifre pe care vi-l trimitem prin email de fiecare data cand va autentificati sau efectuati actiuni sensibile. Verificati emailul pentru un cod din 6 cifre pentru a finaliza configurarea.',

        'form' => [

            'code' => [

                'label' => 'Introduceti codul din 6 cifre pe care vi l-am trimis prin email',

                'validation_attribute' => 'cod',

                'actions' => [

                    'resend' => [

                        'label' => 'Trimite un cod nou prin email',

                        'notifications' => [

                            'resent' => [
                                'title' => 'V-am trimis un cod nou prin email',
                            ],

                            'throttled' => [
                                'title' => 'Prea multe incercari de retrimitere. Va rugam sa asteptati inainte de a solicita alt cod.',
                            ],

                        ],

                    ],

                ],

                'messages' => [

                    'invalid' => 'Codul introdus este invalid.',

                ],

            ],

        ],

        'actions' => [

            'submit' => [
                'label' => 'Activeaza codurile de verificare prin email',
            ],

        ],

    ],

    'notifications' => [

        'enabled' => [
            'title' => 'Codurile de verificare prin email au fost activate',
        ],

    ],

];
