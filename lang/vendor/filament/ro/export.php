<?php

return [

    'label' => 'Exporta :label',

    'modal' => [

        'heading' => 'Exporta :label',

        'form' => [

            'columns' => [

                'label' => 'Coloane',

                'actions' => [

                    'select_all' => [
                        'label' => 'Selecteaza toate',
                    ],

                    'deselect_all' => [
                        'label' => 'Deselecteaza toate',
                    ],

                ],

                'form' => [

                    'is_enabled' => [
                        'label' => ':column activat',
                    ],

                    'label' => [
                        'label' => 'Eticheta :column',
                    ],

                ],

            ],

        ],

        'actions' => [

            'export' => [
                'label' => 'Exporta',
            ],

        ],

    ],

    'notifications' => [

        'completed' => [

            'title' => 'Export complet',

            'actions' => [

                'download_csv' => [
                    'label' => 'Descarca .csv',
                ],

                'download_xlsx' => [
                    'label' => 'Descarca .xlsx',
                ],

            ],

        ],

        'max_rows' => [
            'title' => 'Exportul este prea mare',
            'body' => 'Nu puteti exporta mai mult de 1 rand odata.|Nu puteti exporta mai mult de :count randuri odata.',
        ],

        'no_columns' => [
            'title' => 'Nicio coloana selectata',
            'body' => 'Va rugam sa selectati cel putin o coloana pentru export.',
        ],

        'started' => [
            'title' => 'Exportul a inceput',
            'body' => 'Exportul dvs. a inceput si 1 rand va fi procesat in fundal.|Exportul dvs. a inceput si :count randuri vor fi procesate in fundal.',
        ],

    ],

    'file_name' => 'export-:export_id-:model',

];
