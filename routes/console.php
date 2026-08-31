<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\MenuCatalogSeeder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('oms:import-menu-catalog', function () {
    app(MenuCatalogSeeder::class)->run();

    $this->info('Catalog importat: 12 feluri principale, 4 ciorbe si 6 deserturi/gustari. Programarile existente nu au fost modificate.');
})->purpose('Importa catalogul OMS fara a modifica programarile existente');
