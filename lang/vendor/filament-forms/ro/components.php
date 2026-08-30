<?php

return [

    'builder' => [

        'actions' => [

            'clone' => [
                'label' => 'Cloneaza',
            ],

            'add' => [

                'label' => 'Adaugare la :label',

                'modal' => [

                    'heading' => 'Adaugare la :label',

                    'actions' => [

                        'add' => [
                            'label' => 'Adauga',
                        ],

                    ],

                ],

            ],

            'add_between' => [

                'label' => 'Insereaza intre blocuri',

                'modal' => [

                    'heading' => 'Adaugare la :label',

                    'actions' => [

                        'add' => [
                            'label' => 'Adauga',
                        ],

                    ],

                ],

            ],

            'delete' => [
                'label' => 'Stergere',
            ],

            'edit' => [

                'label' => 'Editare',

                'modal' => [

                    'heading' => 'Editare bloc',

                    'actions' => [

                        'save' => [
                            'label' => 'Salveaza modificarile',
                        ],

                    ],

                ],

            ],

            'reorder' => [
                'label' => 'Mutare',
            ],

            'move_down' => [
                'label' => 'Mutare in jos',
            ],

            'move_up' => [
                'label' => 'Mutare in sus',
            ],

            'collapse' => [
                'label' => 'Comprimare',
            ],

            'expand' => [
                'label' => 'Expandare',
            ],

            'collapse_all' => [
                'label' => 'Comprimare toate',
            ],

            'expand_all' => [
                'label' => 'Expandare toate',
            ],

        ],

    ],

    'checkbox_list' => [

        'actions' => [

            'deselect_all' => [
                'label' => 'Deselecteaza toate',
            ],

            'select_all' => [
                'label' => 'Selecteaza toate',
            ],

        ],

    ],

    'file_upload' => [

        'editor' => [

            'actions' => [

                'cancel' => [
                    'label' => 'Anulare',
                ],

                'drag_crop' => [
                    'label' => 'Mod de glisare "decupare"',
                ],

                'drag_move' => [
                    'label' => 'Mod de glisare "mutare"',
                ],

                'flip_horizontal' => [
                    'label' => 'Intoarce imaginea pe orizontala',
                ],

                'flip_vertical' => [
                    'label' => 'Intoarce imaginea pe verticala',
                ],

                'move_down' => [
                    'label' => 'Muta imaginea in jos',
                ],

                'move_left' => [
                    'label' => 'Muta imaginea in stanga',
                ],

                'move_right' => [
                    'label' => 'Muta imaginea in dreapta',
                ],

                'move_up' => [
                    'label' => 'Muta imaginea in sus',
                ],

                'reset' => [
                    'label' => 'Resetare',
                ],

                'rotate_left' => [
                    'label' => 'Roteste imaginea spre stanga',
                ],

                'rotate_right' => [
                    'label' => 'Roteste imaginea spre dreapta',
                ],

                'set_aspect_ratio' => [
                    'label' => 'Seteaza raportul de aspect la :ratio',
                ],

                'save' => [
                    'label' => 'Salvare',
                ],

                'zoom_100' => [
                    'label' => 'Mareste imaginea la 100%',
                ],

                'zoom_in' => [
                    'label' => 'Mareste',
                ],

                'zoom_out' => [
                    'label' => 'Micsoreaza',
                ],

            ],

            'fields' => [

                'height' => [
                    'label' => 'Inaltime',
                    'unit' => 'px',
                ],

                'rotation' => [
                    'label' => 'Rotire',
                    'unit' => 'grade',
                ],

                'width' => [
                    'label' => 'Latime',
                    'unit' => 'px',
                ],

                'x_position' => [
                    'label' => 'X',
                    'unit' => 'px',
                ],

                'y_position' => [
                    'label' => 'Y',
                    'unit' => 'px',
                ],

            ],

            'aspect_ratios' => [

                'label' => 'Rapoarte de aspect',

                'no_fixed' => [
                    'label' => 'Liber',
                ],

            ],

            'svg' => [

                'messages' => [
                    'confirmation' => 'Editarea fisierelor SVG nu este recomandata deoarece poate rezulta in pierderea calitatii la scalare.\n Sunteti sigur ca doriti sa continuati?',
                    'disabled' => 'Editarea fisierelor SVG este dezactivata deoarece poate rezulta in pierderea calitatii la scalare.',
                ],

            ],

        ],

    ],

    'key_value' => [

        'actions' => [

            'add' => [
                'label' => 'Adaugare rand',
            ],

            'delete' => [
                'label' => 'Stergere rand',
            ],

            'reorder' => [
                'label' => 'Reordonare rand',
            ],

        ],

        'fields' => [

            'key' => [
                'label' => 'Cheie',
            ],

            'value' => [
                'label' => 'Valoare',
            ],

        ],

    ],

    'markdown_editor' => [

        'file_attachments_accepted_file_types_message' => 'Fisierele incarcate trebuie sa fie de tipul: :values.',

        'file_attachments_max_size_message' => 'Fisierele incarcate nu trebuie sa depaseasca :max kiloocteti.',

        'tools' => [
            'attach_files' => 'Atasare fisiere',
            'blockquote' => 'Citat',
            'bold' => 'Ingrosat',
            'bullet_list' => 'Lista cu puncte',
            'code_block' => 'Bloc de cod',
            'heading' => 'Titlu',
            'italic' => 'Cursiv',
            'link' => 'Link',
            'ordered_list' => 'Lista numerotata',
            'redo' => 'Refa',
            'strike' => 'Taiat',
            'table' => 'Tabel',
            'undo' => 'Anuleaza',
        ],

    ],

    'modal_table_select' => [

        'actions' => [

            'select' => [

                'label' => 'Selecteaza',

                'actions' => [

                    'select' => [
                        'label' => 'Selecteaza',
                    ],

                ],

            ],

        ],

    ],

    'radio' => [

        'boolean' => [
            'true' => 'Da',
            'false' => 'Nu',
        ],

    ],

    'repeater' => [

        'actions' => [

            'add' => [
                'label' => 'Adaugare la :label',
            ],

            'add_between' => [
                'label' => 'Insereaza',
            ],

            'delete' => [
                'label' => 'Stergere',
            ],

            'clone' => [
                'label' => 'Cloneaza',
            ],

            'reorder' => [
                'label' => 'Mutare',
            ],

            'move_down' => [
                'label' => 'Mutare in jos',
            ],

            'move_up' => [
                'label' => 'Mutare in sus',
            ],

            'collapse' => [
                'label' => 'Comprimare',
            ],

            'expand' => [
                'label' => 'Expandare',
            ],

            'collapse_all' => [
                'label' => 'Comprimare toate',
            ],

            'expand_all' => [
                'label' => 'Expandare toate',
            ],

        ],

    ],

    'rich_editor' => [

        'actions' => [

            'attach_files' => [

                'label' => 'Incarcare fisier',

                'modal' => [

                    'heading' => 'Incarcare fisier',

                    'form' => [

                        'file' => [

                            'label' => [
                                'new' => 'Fisier',
                                'existing' => 'Inlocuire fisier',
                            ],

                        ],

                        'alt' => [

                            'label' => [
                                'new' => 'Text alternativ',
                                'existing' => 'Modificare text alternativ',
                            ],

                        ],

                    ],

                ],

            ],

            'custom_block' => [

                'modal' => [

                    'actions' => [

                        'insert' => [
                            'label' => 'Insereaza',
                        ],

                        'save' => [
                            'label' => 'Salveaza',
                        ],

                    ],

                ],

            ],

            'grid' => [

                'label' => 'Grila',

                'modal' => [

                    'heading' => 'Grila',

                    'form' => [

                        'preset' => [

                            'label' => 'Presetare',

                            'placeholder' => 'Niciunul',

                            'options' => [
                                'two' => 'Doua',
                                'three' => 'Trei',
                                'four' => 'Patru',
                                'five' => 'Cinci',
                                'two_start_third' => 'Doua (Prima treime)',
                                'two_end_third' => 'Doua (Ultima treime)',
                                'two_start_fourth' => 'Doua (Prima patrime)',
                                'two_end_fourth' => 'Doua (Ultima patrime)',
                            ],
                        ],

                        'columns' => [
                            'label' => 'Coloane',
                        ],

                        'from_breakpoint' => [

                            'label' => 'De la breakpoint',

                            'options' => [
                                'default' => 'Toate',
                                'sm' => 'Mic',
                                'md' => 'Mediu',
                                'lg' => 'Mare',
                                'xl' => 'Extra mare',
                                '2xl' => 'Dublu extra mare',
                            ],

                        ],

                        'is_asymmetric' => [
                            'label' => 'Doua coloane asimetrice',
                        ],

                        'start_span' => [
                            'label' => 'Span inceput',
                        ],

                        'end_span' => [
                            'label' => 'Span sfarsit',
                        ],

                    ],

                ],

            ],

            'link' => [

                'label' => 'Link',

                'modal' => [

                    'heading' => 'Link',

                    'form' => [

                        'url' => [
                            'label' => 'URL',
                        ],

                        'should_open_in_new_tab' => [
                            'label' => 'Deschide intr-o fila noua',
                        ],

                    ],

                ],

            ],

            'text_color' => [

                'label' => 'Culoare text',

                'modal' => [

                    'heading' => 'Culoare text',

                    'form' => [

                        'color' => [
                            'label' => 'Culoare',
                        ],

                        'custom_color' => [
                            'label' => 'Culoare personalizata',
                        ],

                    ],

                ],

            ],

        ],

        'file_attachments_accepted_file_types_message' => 'Fisierele incarcate trebuie sa fie de tipul: :values.',

        'file_attachments_max_size_message' => 'Fisierele incarcate nu trebuie sa depaseasca :max kiloocteti.',

        'no_merge_tag_search_results_message' => 'Nu s-au gasit etichete de imbinare.',

        'mentions' => [
            'no_options_message' => 'Nu sunt disponibile optiuni.',
            'no_search_results_message' => 'Niciun rezultat nu corespunde cautarii.',
            'search_prompt' => 'Incepeti sa tastati pentru a cauta...',
            'searching_message' => 'Se cauta...',
        ],

        'tools' => [
            'align_center' => 'Aliniere centru',
            'align_end' => 'Aliniere dreapta',
            'align_justify' => 'Aliniere justificata',
            'align_start' => 'Aliniere stanga',
            'attach_files' => 'Atasare fisiere',
            'blockquote' => 'Citat',
            'bold' => 'Ingrosat',
            'bullet_list' => 'Lista cu puncte',
            'clear_formatting' => 'Curata formatarea',
            'code' => 'Cod',
            'code_block' => 'Bloc de cod',
            'custom_blocks' => 'Blocuri',
            'details' => 'Detalii',
            'h1' => 'Titlu',
            'h2' => 'Subtitlu',
            'h3' => 'Subtitlu mic',
            'grid' => 'Grila',
            'grid_delete' => 'Sterge grila',
            'highlight' => 'Evidentiere',
            'horizontal_rule' => 'Linie orizontala',
            'italic' => 'Cursiv',
            'lead' => 'Text principal',
            'link' => 'Link',
            'merge_tags' => 'Etichete de imbinare',
            'ordered_list' => 'Lista numerotata',
            'redo' => 'Refa',
            'small' => 'Text mic',
            'strike' => 'Taiat',
            'subscript' => 'Indice',
            'superscript' => 'Exponent',
            'table' => 'Tabel',
            'table_delete' => 'Sterge tabelul',
            'table_add_column_before' => 'Adauga coloana inainte',
            'table_add_column_after' => 'Adauga coloana dupa',
            'table_delete_column' => 'Sterge coloana',
            'table_add_row_before' => 'Adauga rand deasupra',
            'table_add_row_after' => 'Adauga rand dedesubt',
            'table_delete_row' => 'Sterge randul',
            'table_merge_cells' => 'Imbina celulele',
            'table_split_cell' => 'Separa celula',
            'table_toggle_header_row' => 'Comuta randul antet',
            'table_toggle_header_cell' => 'Comuta celula antet',
            'text_color' => 'Culoare text',
            'underline' => 'Subliniat',
            'undo' => 'Anuleaza',
        ],

        'uploading_file_message' => 'Se incarca fisierul...',

    ],

    'select' => [

        'actions' => [

            'create_option' => [

                'label' => 'Creeaza',

                'modal' => [

                    'heading' => 'Creeaza',

                    'actions' => [

                        'create' => [
                            'label' => 'Creeaza',
                        ],

                        'create_another' => [
                            'label' => 'Creeaza si adauga altul',
                        ],

                    ],

                ],

            ],

            'edit_option' => [

                'label' => 'Editeaza',

                'modal' => [

                    'heading' => 'Editare',

                    'actions' => [

                        'save' => [
                            'label' => 'Salvare',
                        ],

                    ],

                ],

            ],

        ],

        'boolean' => [
            'true' => 'Da',
            'false' => 'Nu',
        ],

        'loading_message' => 'Se incarca...',

        'max_items_message' => 'Pot fi selectate doar :count.',

        'no_options_message' => 'Nu sunt disponibile optiuni.',

        'no_search_results_message' => 'Nicio optiune nu corespunde cautarii.',

        'placeholder' => 'Selectati o optiune',

        'searching_message' => 'Se cauta...',

        'search_prompt' => 'Incepeti sa tastati pentru a cauta...',

    ],

    'tags_input' => [

        'actions' => [

            'delete' => [
                'label' => 'Stergere',
            ],

        ],

        'placeholder' => 'Eticheta noua',

    ],

    'text_input' => [

        'actions' => [

            'copy' => [
                'label' => 'Copiaza',
                'message' => 'Copiat',
            ],

            'hide_password' => [
                'label' => 'Ascunde parola',
            ],

            'show_password' => [
                'label' => 'Afiseaza parola',
            ],

        ],

    ],

    'toggle_buttons' => [

        'boolean' => [
            'true' => 'Da',
            'false' => 'Nu',
        ],

    ],

];
