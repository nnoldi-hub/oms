<?php

return [

    'management_schema' => [

        'actions' => [

            'label' => 'Coduri de verificare prin email',

            'below_content' => 'Primiti un cod temporar la adresa dumneavoastra de email pentru a va verifica identitatea la autentificare.',

            'messages' => [
                'enabled' => 'Activat',
                'disabled' => 'Dezactivat',
            ],

        ],

    ],

    'login_form' => [

        'label' => 'Trimite un cod pe email',

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

];
