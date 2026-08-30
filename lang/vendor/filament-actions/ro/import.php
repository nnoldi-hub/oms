<?php

return [

    'label' => 'Importa :label',

    'modal' => [

        'heading' => 'Importa :label',

        'form' => [

            'file' => [

                'label' => 'Fisier',

                'placeholder' => 'Incarca un fisier CSV',

                'rules' => [
                    'duplicate_columns' => '{0} Fisierul nu poate contine mai mult de un antet de coloana gol.|{1,*} Fisierul nu poate contine antete de coloane duplicate: :columns.',
                ],

            ],

            'columns' => [
                'label' => 'Coloane',
                'placeholder' => 'Alege Coloanele',
            ],

        ],

        'actions' => [

            'download_example' => [
                'label' => 'Descarca exemplu in format CSV',
            ],

            'import' => [
                'label' => 'Importa',
            ],

        ],

    ],

    'notifications' => [

        'completed' => [

            'title' => 'Import finalizat',

            'actions' => [

                'download_failed_rows_csv' => [
                    'label' => 'Descarca informatiiler despre campul ce a avut eroaro|Descarca informatii despre campurile ce au auvt erori',
                ],

            ],

        ],

        'max_rows' => [
            'title' => 'Fisierul CSV file este prea mare',
            'body' => 'Nu este permis importul a mai mult de o linile intr-un singur fisier.|Nu este permis imporutl a mai mult de :count linii intr-un singur fisier.',
        ],

        'started' => [
            'title' => 'Importul a inceput',
            'body' => 'Importul dumneavostra a inceput, iar randul va fi procesa in fundal.|Importul dumneavoastra a inceput, iar cele :count randuri vor fi procesate in fundal.',
        ],

    ],

    'example_csv' => [
        'file_name' => ':importer-example',
    ],

    'failure_csv' => [
        'file_name' => 'import-:import_id-:csv_name-randuri-esuate',
        'error_header' => 'eroare',
        'system_error' => 'Eroare de sistem, va rugam contactati echipa de suport.',
        'column_mapping_required_for_new_record' => 'Coloana :attribute nu a fost mapata la o coloana din fisier, dar este necesara pentru crearea inregistrarilor noi.',
    ],

];
