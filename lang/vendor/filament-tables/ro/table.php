<?php

return [

    'column_manager' => [

        'heading' => 'Coloane',

        'actions' => [

            'apply' => [
                'label' => 'Aplica coloane',
            ],

            'reset' => [
                'label' => 'Resetare',
            ],

        ],

    ],

    'columns' => [

        'actions' => [
            'label' => 'Actiune|Actiuni',
        ],

        'select' => [

            'loading_message' => 'Se incarca...',

            'no_options_message' => 'Nu sunt disponibile optiuni.',

            'no_search_results_message' => 'Nicio optiune nu corespunde cautarii.',

            'placeholder' => 'Selectati o optiune',

            'searching_message' => 'Se cauta...',

            'search_prompt' => 'Incepeti sa tastati pentru a cauta...',

        ],

        'text' => [

            'actions' => [
                'collapse_list' => 'Afiseaza cu :count mai putin',
                'expand_list' => 'Afiseaza cu :count mai mult',
            ],

            'more_list_items' => 'si alte :count',

        ],

    ],

    'fields' => [

        'bulk_select_page' => [
            'label' => 'Selectati/Deselectati tot pentru operatiuni in masa.',
        ],

        'bulk_select_record' => [
            'label' => 'Selecteaza/Deselecteaza elementul :key pentru operatiuni in masa.',
        ],

        'bulk_select_group' => [
            'label' => 'Selecteaza/Deselecteaza grupul :title pentru operatiuni in masa.',
        ],

        'search' => [
            'label' => 'Cautare',
            'placeholder' => 'Cautare',
            'indicator' => 'Cautare',
        ],

    ],

    'summary' => [

        'heading' => 'Sumar',

        'subheadings' => [
            'all' => 'Toate :label',
            'group' => 'Sumar :group',
            'page' => 'Aceasta pagina',
        ],

        'summarizers' => [

            'average' => [
                'label' => 'Medie',
            ],

            'count' => [
                'label' => 'Numarare',
            ],

            'sum' => [
                'label' => 'Suma',
            ],

        ],

    ],

    'actions' => [

        'disable_reordering' => [
            'label' => 'Finalizeaza reordonarea inregistrarilor',
        ],

        'enable_reordering' => [
            'label' => 'Reordoneaza inregistrarile',
        ],

        'filter' => [
            'label' => 'Filtru',
        ],

        'group' => [
            'label' => 'Grupare',
        ],

        'open_bulk_actions' => [
            'label' => 'Operatiuni in masa',
        ],

        'column_manager' => [
            'label' => 'Manager coloane',
        ],

    ],

    'empty' => [

        'heading' => 'Niciun :model',

        'description' => 'Creeaza un :model pentru a incepe.',

    ],

    'filters' => [

        'actions' => [

            'apply' => [
                'label' => 'Aplica filtrele',
            ],

            'remove' => [
                'label' => 'Elimina filtrul',
            ],

            'remove_all' => [
                'label' => 'Elimina toate filtrele',
                'tooltip' => 'Elimina toate filtrele',
            ],

            'reset' => [
                'label' => 'Resetare',
            ],

        ],

        'heading' => 'Filtre',

        'indicator' => 'Filtre active',

        'multi_select' => [
            'placeholder' => 'Toate',
        ],

        'select' => [

            'placeholder' => 'Toate',

            'relationship' => [
                'empty_option_label' => 'Niciunul',
            ],

        ],

        'trashed' => [

            'label' => 'Inregistrari sterse',

            'only_trashed' => 'Doar inregistrarile sterse',

            'with_trashed' => 'Cu inregistrari sterse',

            'without_trashed' => 'Fara inregistrari sterse',

        ],

    ],

    'grouping' => [

        'fields' => [

            'group' => [
                'label' => 'Grupeaza dupa',
            ],

            'direction' => [

                'label' => 'Directie grupare',

                'options' => [
                    'asc' => 'Ascendenta',
                    'desc' => 'Descendenta',
                ],

            ],

        ],

    ],

    'reorder_indicator' => 'Trageti si plasati inregistrarile in ordinea dorita.',

    'selection_indicator' => [

        'selected_count' => '1 inregistrare selectata|:count inregistrari selectate',

        'actions' => [

            'select_all' => [
                'label' => 'Selecteaza toate :count',
            ],

            'deselect_all' => [
                'label' => 'Deselecteaza toate',
            ],

        ],

    ],

    'sorting' => [

        'fields' => [

            'column' => [
                'label' => 'Sorteaza dupa',
            ],

            'direction' => [

                'label' => 'Directie sortare',

                'options' => [
                    'asc' => 'Ascendenta',
                    'desc' => 'Descendenta',
                ],

            ],

        ],

    ],

    'default_model_label' => 'inregistrare',

];
