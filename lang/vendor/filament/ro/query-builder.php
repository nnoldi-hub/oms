<?php

return [

    'label' => 'Constructor interogari',

    'form' => [

        'operator' => [
            'label' => 'Operator',
        ],

        'or_groups' => [

            'label' => 'Grupuri',

            'block' => [
                'label' => 'Conditie SAU',
                'or' => 'SAU',
            ],

        ],

        'rules' => [

            'label' => 'Reguli',

            'item' => [
                'and' => 'SI',
            ],

        ],

    ],

    'no_rules' => '(Fara reguli)',

    'item_separators' => [
        'and' => 'SI',
        'or' => 'SAU',
    ],

    'operators' => [

        'is_filled' => [

            'label' => [
                'direct' => 'Este completat',
                'inverse' => 'Este gol',
            ],

            'summary' => [
                'direct' => ':attribute este completat',
                'inverse' => ':attribute este gol',
            ],

        ],

        'boolean' => [

            'is_true' => [

                'label' => [
                    'direct' => 'Este adevarat',
                    'inverse' => 'Este fals',
                ],

                'summary' => [
                    'direct' => ':attribute este adevarat',
                    'inverse' => ':attribute este fals',
                ],

            ],

        ],

        'date' => [

            'is_after' => [

                'label' => [
                    'direct' => 'Este dupa',
                    'inverse' => 'Nu este dupa',
                ],

                'summary' => [
                    'direct' => ':attribute este dupa :date',
                    'inverse' => ':attribute nu este dupa :date',
                ],

            ],

            'is_before' => [

                'label' => [
                    'direct' => 'Este inainte',
                    'inverse' => 'Nu este inainte',
                ],

                'summary' => [
                    'direct' => ':attribute este inainte de :date',
                    'inverse' => ':attribute nu este inainte de :date',
                ],

            ],

            'is_date' => [

                'label' => [
                    'direct' => 'Este data',
                    'inverse' => 'Nu este data',
                ],

                'summary' => [
                    'direct' => ':attribute este :date',
                    'inverse' => ':attribute nu este :date',
                ],

            ],

            'is_month' => [

                'label' => [
                    'direct' => 'Este luna',
                    'inverse' => 'Nu este luna',
                ],

                'summary' => [
                    'direct' => ':attribute este :month',
                    'inverse' => ':attribute nu este :month',
                ],

            ],

            'is_year' => [

                'label' => [
                    'direct' => 'Este anul',
                    'inverse' => 'Nu este anul',
                ],

                'summary' => [
                    'direct' => ':attribute este :year',
                    'inverse' => ':attribute nu este :year',
                ],

            ],

            'unit_labels' => [
                'second' => 'Secunde',
                'minute' => 'Minute',
                'hour' => 'Ore',
                'day' => 'Zile',
                'week' => 'Saptamani',
                'month' => 'Luni',
                'quarter' => 'Trimestre',
                'year' => 'Ani',
            ],

            'presets' => [
                'past_decade' => 'Ultimul deceniu',
                'past_5_years' => 'Ultimii 5 ani',
                'past_2_years' => 'Ultimii 2 ani',
                'past_year' => 'Ultimul an',
                'past_6_months' => 'Ultimele 6 luni',
                'past_quarter' => 'Ultimul trimestru',
                'past_month' => 'Ultima luna',
                'past_2_weeks' => 'Ultimele 2 saptamani',
                'past_week' => 'Ultima saptamana',
                'past_hour' => 'Ultima ora',
                'past_minute' => 'Ultimul minut',
                'this_decade' => 'Acest deceniu',
                'this_year' => 'Acest an',
                'this_quarter' => 'Acest trimestru',
                'this_month' => 'Aceasta luna',
                'today' => 'Astazi',
                'this_hour' => 'Aceasta ora',
                'this_minute' => 'Acest minut',
                'next_minute' => 'Urmatorul minut',
                'next_hour' => 'Urmatoarea ora',
                'next_week' => 'Saptamana viitoare',
                'next_2_weeks' => 'Urmatoarele 2 saptamani',
                'next_month' => 'Luna viitoare',
                'next_quarter' => 'Trimestrul viitor',
                'next_6_months' => 'Urmatoarele 6 luni',
                'next_year' => 'Anul viitor',
                'next_2_years' => 'Urmatorii 2 ani',
                'next_5_years' => 'Urmatorii 5 ani',
                'next_decade' => 'Urmatorul deceniu',
                'custom' => 'Personalizat',
            ],

            'form' => [

                'date' => [
                    'label' => 'Data',
                ],

                'month' => [
                    'label' => 'Luna',
                ],

                'year' => [
                    'label' => 'An',
                ],

                'mode' => [

                    'label' => 'Tip data',

                    'options' => [
                        'absolute' => 'Data specifica',
                        'relative' => 'Fereastra relativa',
                    ],

                ],

                'preset' => [
                    'label' => 'Perioada de timp',
                ],

                'relative_value' => [
                    'label' => 'Cate',
                ],

                'relative_unit' => [
                    'label' => 'Unitate de timp',
                ],

                'tense' => [

                    'label' => 'Timp',

                    'options' => [
                        'past' => 'Trecut',
                        'future' => 'Viitor',
                    ],

                ],

            ],

        ],

        'number' => [

            'equals' => [

                'label' => [
                    'direct' => 'Este egal cu',
                    'inverse' => 'Nu este egal cu',
                ],

                'summary' => [
                    'direct' => ':attribute este egal cu :number',
                    'inverse' => ':attribute nu este egal cu :number',
                ],

            ],

            'is_max' => [

                'label' => [
                    'direct' => 'Este maxim',
                    'inverse' => 'Este mai mare decat',
                ],

                'summary' => [
                    'direct' => ':attribute este maxim :number',
                    'inverse' => ':attribute este mai mare decat :number',
                ],

            ],

            'is_min' => [

                'label' => [
                    'direct' => 'Este minim',
                    'inverse' => 'Este mai mic decat',
                ],

                'summary' => [
                    'direct' => ':attribute este minim :number',
                    'inverse' => ':attribute este mai mic decat :number',
                ],

            ],

            'aggregates' => [

                'average' => [
                    'label' => 'Medie',
                    'summary' => 'Media :attribute',
                ],

                'max' => [
                    'label' => 'Max',
                    'summary' => 'Max :attribute',
                ],

                'min' => [
                    'label' => 'Min',
                    'summary' => 'Min :attribute',
                ],

                'sum' => [
                    'label' => 'Suma',
                    'summary' => 'Suma :attribute',
                ],

            ],

            'form' => [

                'aggregate' => [
                    'label' => 'Agregare',
                ],

                'number' => [
                    'label' => 'Numar',
                ],

            ],

        ],

        'relationship' => [

            'equals' => [

                'label' => [
                    'direct' => 'Are',
                    'inverse' => 'Nu are',
                ],

                'summary' => [
                    'direct' => 'Are :count :relationship',
                    'inverse' => 'Nu are :count :relationship',
                ],

            ],

            'has_max' => [

                'label' => [
                    'direct' => 'Are maxim',
                    'inverse' => 'Are mai mult de',
                ],

                'summary' => [
                    'direct' => 'Are maxim :count :relationship',
                    'inverse' => 'Are mai mult de :count :relationship',
                ],

            ],

            'has_min' => [

                'label' => [
                    'direct' => 'Are minim',
                    'inverse' => 'Are mai putin de',
                ],

                'summary' => [
                    'direct' => 'Are minim :count :relationship',
                    'inverse' => 'Are mai putin de :count :relationship',
                ],

            ],

            'is_empty' => [

                'label' => [
                    'direct' => 'Este gol',
                    'inverse' => 'Nu este gol',
                ],

                'summary' => [
                    'direct' => ':relationship este gol',
                    'inverse' => ':relationship nu este gol',
                ],

            ],

            'is_related_to' => [

                'label' => [

                    'single' => [
                        'direct' => 'Este',
                        'inverse' => 'Nu este',
                    ],

                    'multiple' => [
                        'direct' => 'Contine',
                        'inverse' => 'Nu contine',
                    ],

                ],

                'summary' => [

                    'single' => [
                        'direct' => ':relationship este :values',
                        'inverse' => ':relationship nu este :values',
                    ],

                    'multiple' => [
                        'direct' => ':relationship contine :values',
                        'inverse' => ':relationship nu contine :values',
                    ],

                    'values_glue' => [
                        0 => ', ',
                        'final' => ' sau ',
                    ],

                ],

                'form' => [

                    'value' => [
                        'label' => 'Valoare',
                    ],

                    'values' => [
                        'label' => 'Valori',
                    ],

                ],

            ],

            'form' => [

                'count' => [
                    'label' => 'Numar',
                ],

            ],

        ],

        'select' => [

            'is' => [

                'label' => [
                    'direct' => 'Este',
                    'inverse' => 'Nu este',
                ],

                'summary' => [
                    'direct' => ':attribute este :values',
                    'inverse' => ':attribute nu este :values',
                    'values_glue' => [
                        ', ',
                        'final' => ' sau ',
                    ],
                ],

                'form' => [

                    'value' => [
                        'label' => 'Valoare',
                    ],

                    'values' => [
                        'label' => 'Valori',
                    ],

                ],

            ],

        ],

        'text' => [

            'contains' => [

                'label' => [
                    'direct' => 'Contine',
                    'inverse' => 'Nu contine',
                ],

                'summary' => [
                    'direct' => ':attribute contine :text',
                    'inverse' => ':attribute nu contine :text',
                ],

            ],

            'ends_with' => [

                'label' => [
                    'direct' => 'Se termina cu',
                    'inverse' => 'Nu se termina cu',
                ],

                'summary' => [
                    'direct' => ':attribute se termina cu :text',
                    'inverse' => ':attribute nu se termina cu :text',
                ],

            ],

            'equals' => [

                'label' => [
                    'direct' => 'Este egal cu',
                    'inverse' => 'Nu este egal cu',
                ],

                'summary' => [
                    'direct' => ':attribute este egal cu :text',
                    'inverse' => ':attribute nu este egal cu :text',
                ],

            ],

            'starts_with' => [

                'label' => [
                    'direct' => 'Incepe cu',
                    'inverse' => 'Nu incepe cu',
                ],

                'summary' => [
                    'direct' => ':attribute incepe cu :text',
                    'inverse' => ':attribute nu incepe cu :text',
                ],

            ],

            'form' => [

                'text' => [
                    'label' => 'Text',
                ],

            ],

        ],

    ],

    'actions' => [

        'add_rule' => [
            'label' => 'Adauga regula',
        ],

        'add_rule_group' => [
            'label' => 'Adauga SAU',
        ],

    ],

];
