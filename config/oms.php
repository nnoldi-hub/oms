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
];
