<?php

return [

    'label' => 'Navigare',

    'overview' => 'Afisare :first-:last din :total rezultate',

    'fields' => [

        'records_per_page' => [

            'label' => 'Pe pagina',

            'options' => [
                'all' => 'Toate',
            ],

        ],

    ],

    'actions' => [

        'first' => [
            'label' => 'Prima pagina',
        ],

        'go_to_page' => [
            'label' => 'Mergi la pagina :page',
        ],

        'last' => [
            'label' => 'Ultima pagina',
        ],

        'next' => [
            'label' => 'Pagina urmatoare',
        ],

        'previous' => [
            'label' => 'Pagina precedenta',
        ],

    ],

];
