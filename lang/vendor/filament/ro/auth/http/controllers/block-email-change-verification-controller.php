<?php

return [

    'notifications' => [

        'blocked' => [
            'title' => 'Schimbarea adresei de email a fost blocata',
            'body' => 'Ati blocat cu succes o tentativa de schimbare a adresei de email la :email. Daca nu ati facut cererea initiala, va rugam sa ne contactati imediat.',
        ],

        'failed' => [
            'title' => 'Blocarea schimbarii adresei de email a esuat',
            'body' => 'Din pacate, nu ati putut preveni schimbarea adresei de email la :email, deoarece aceasta a fost deja verificata inainte sa o blocati. Daca nu ati facut cererea initiala, va rugam sa ne contactati imediat.',
        ],

    ],

];
