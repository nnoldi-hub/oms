<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prag de deviatie a costului real
    |--------------------------------------------------------------------------
    |
    | Cand un coordonator introduce un cost real cu mai mult de acest procent
    | peste costul estimat, administratorii primesc o notificare.
    |
    */
    'actual_cost_deviation_threshold' => env('OMS_ACTUAL_COST_DEVIATION_THRESHOLD', 0.25),

    /*
    |--------------------------------------------------------------------------
    | Marja de siguranta a portiilor
    |--------------------------------------------------------------------------
    |
    | Procentul cu care se majoreaza numarul de portii la generarea retetei si a
    | listei de cumparaturi, ca sa acoperim zilele in care numarul exact de
    | persoane nu este inca stabilit. Valoarea se rotunjeste in sus la portii
    | intregi. Ex.: 0.1 = +10%.
    |
    */
    'portion_safety_margin' => env('OMS_PORTION_SAFETY_MARGIN', 0.1),

    /*
    |--------------------------------------------------------------------------
    | Contact pentru donatii
    |--------------------------------------------------------------------------
    |
    | Numele si telefonul persoanei care primeste donatiile de alimente.
    | Valorile reale se pastreaza doar in .env sau in configurarea serverului.
    |
    */
    'donations_contact_name' => env('OMS_DONATIONS_CONTACT_NAME', ''),
    'donations_contact_phone' => env('OMS_DONATIONS_CONTACT_PHONE', ''),
];
