<?php

return [

    'single' => [

        'label' => 'Stergerea fortata',

        'modal' => [

            'heading' => 'Stergere fortata :label',

            'actions' => [

                'delete' => [
                    'label' => 'Stergere',
                ],

            ],

        ],

        'notifications' => [

            'deleted' => [
                'title' => 'Sters cu succes',
            ],

        ],

    ],

    'multiple' => [

        'label' => 'Stergere fortata inregistrarile selectate',

        'modal' => [

            'heading' => 'Stergere fortata :label selectate',

            'actions' => [

                'delete' => [
                    'label' => 'Stergere',
                ],

            ],

        ],

        'notifications' => [

            'deleted' => [
                'title' => 'Sters cu succes',
            ],

            'deleted_partial' => [
                'title' => 'S-au sters :count din :total',
                'missing_authorization_failure_message' => 'Nu aveti permisiunea de a sterge :count.',
                'missing_processing_failure_message' => ':count nu au putut fi sterse.',
            ],

            'deleted_none' => [
                'title' => 'Stergerea a esuat',
                'missing_authorization_failure_message' => 'Nu aveti permisiunea de a sterge :count.',
                'missing_processing_failure_message' => ':count nu au putut fi sterse.',
            ],

        ],

    ],

];
