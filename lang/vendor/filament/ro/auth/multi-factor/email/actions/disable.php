<?php

return [

    'label' => 'Dezactiveaza',

    'modal' => [

        'heading' => 'Dezactivare coduri de verificare prin email',

        'description' => 'Sigur doriti sa nu mai primiti coduri de verificare prin email? Dezactivarea acestora va elimina un nivel suplimentar de securitate din contul dumneavoastra.',

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
                'label' => 'Dezactiveaza codurile de verificare prin email',
            ],

        ],

    ],

    'notifications' => [

        'disabled' => [
            'title' => 'Codurile de verificare prin email au fost dezactivate',
        ],

    ],

];
