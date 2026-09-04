<?php

namespace Database\Seeders;

use App\Models\SupplyItem;
use Illuminate\Database\Seeder;

class SupplyCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Apa plata', 'category' => 'water', 'unit' => 'L'],
            ['name' => 'Apa minerala', 'category' => 'water', 'unit' => 'L'],
            ['name' => 'Gustari ambalate', 'category' => 'snack', 'unit' => 'portie'],
            ['name' => 'Deserturi', 'category' => 'snack', 'unit' => 'portie'],
            ['name' => 'Pahare', 'category' => 'auxiliary', 'unit' => 'buc'],
            ['name' => 'Tacamuri', 'category' => 'auxiliary', 'unit' => 'set'],
            ['name' => 'Servetele', 'category' => 'auxiliary', 'unit' => 'buc'],
            ['name' => 'Saci de gunoi', 'category' => 'auxiliary', 'unit' => 'buc'],
            ['name' => 'Produse de curatenie', 'category' => 'auxiliary', 'unit' => 'set'],
        ] as $item) {
            SupplyItem::firstOrCreate($item);
        }
    }
}
