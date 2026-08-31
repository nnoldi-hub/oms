<?php

namespace Database\Seeders;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Week;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class OmsScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $congregations = collect([
            'Congregatia 1',
            'Congregatia 2',
            'Congregatia 3',
        ])->map(fn (string $name) => Congregation::updateOrCreate(['name' => $name]));

        $menus = collect([
            [
                'name' => 'Tocanita de pui cu mamaliguta si muraturi',
                'instructions' => 'Se serveste calda, cu mamaliguta si muraturi.',
                'ingredients' => [
                    ['name' => 'Pulpe de pui dezosate', 'quantity_per_person' => 0.15, 'unit' => 'kg'],
                    ['name' => 'Ceapa si morcovi', 'quantity_per_person' => 0.05, 'unit' => 'kg'],
                    ['name' => 'Malai', 'quantity_per_person' => 0.08, 'unit' => 'kg'],
                    ['name' => 'Muraturi', 'quantity_per_person' => 0.1, 'unit' => 'kg'],
                ],
                'packaging_cost' => 1.25,
            ],
            [
                'name' => 'Pilaf sarbesc cu carne de porc si salata de varza',
                'instructions' => 'Serveste pilaful cald cu salata de varza separat.',
                'ingredients' => [
                    ['name' => 'Carne de porc', 'quantity_per_person' => 0.12, 'unit' => 'kg'],
                    ['name' => 'Orez rotund', 'quantity_per_person' => 0.08, 'unit' => 'kg'],
                    ['name' => 'Mix legume', 'quantity_per_person' => 0.04, 'unit' => 'kg'],
                    ['name' => 'Varza alba', 'quantity_per_person' => 0.12, 'unit' => 'kg'],
                ],
                'packaging_cost' => 1.1,
            ],
            [
                'name' => 'Penne Bolognese la cuptor cu cascaval',
                'instructions' => 'Adauga sosul si cascavalul inainte de coacere.',
                'ingredients' => [
                    ['name' => 'Carne tocata amestec', 'quantity_per_person' => 0.12, 'unit' => 'kg'],
                    ['name' => 'Paste penne', 'quantity_per_person' => 0.09, 'unit' => 'kg'],
                    ['name' => 'Sos de rosii', 'quantity_per_person' => 0.08, 'unit' => 'kg'],
                    ['name' => 'Cascaval', 'quantity_per_person' => 0.03, 'unit' => 'kg'],
                ],
                'packaging_cost' => 1.15,
            ],
            [
                'name' => 'Mazare scazuta cu piept de pui si paine',
                'instructions' => 'Serveste mazarea calda cu paine feliata.',
                'ingredients' => [
                    ['name' => 'Piept de pui feliat', 'quantity_per_person' => 0.13, 'unit' => 'kg'],
                    ['name' => 'Mazare', 'quantity_per_person' => 0.15, 'unit' => 'kg'],
                    ['name' => 'Paine feliata', 'quantity_per_person' => 2, 'unit' => 'buc'],
                ],
                'packaging_cost' => 1.1,
            ],
            [
                'name' => 'Varza calita cu carnati si mamaliga',
                'instructions' => 'Gateste varza pana scade bine si serveste cu mamaliga.',
                'ingredients' => [
                    ['name' => 'Carnati', 'quantity_per_person' => 0.12, 'unit' => 'kg'],
                    ['name' => 'Varza alba', 'quantity_per_person' => 0.25, 'unit' => 'kg'],
                    ['name' => 'Malai', 'quantity_per_person' => 0.08, 'unit' => 'kg'],
                ],
                'packaging_cost' => 1.15,
            ],
            [
                'name' => 'Chiftelute marinate cu piure de cartofi',
                'instructions' => 'Portioneaza chiftelutele cu sos si piureul separat.',
                'ingredients' => [
                    ['name' => 'Carne tocata', 'quantity_per_person' => 0.12, 'unit' => 'kg'],
                    ['name' => 'Cartofi', 'quantity_per_person' => 0.3, 'unit' => 'kg'],
                    ['name' => 'Lapte si unt', 'quantity_per_person' => 0.04, 'unit' => 'kg'],
                    ['name' => 'Sos de rosii', 'quantity_per_person' => 0.06, 'unit' => 'kg'],
                ],
                'packaging_cost' => 1.2,
            ],
            [
                'name' => 'Cartofi taranesti la cuptor cu costita si salata de sfecla',
                'instructions' => 'Serveste salata de sfecla separat.',
                'ingredients' => [
                    ['name' => 'Cartofi', 'quantity_per_person' => 0.3, 'unit' => 'kg'],
                    ['name' => 'Costita afumata', 'quantity_per_person' => 0.08, 'unit' => 'kg'],
                    ['name' => 'Ceapa si boia', 'quantity_per_person' => 0.03, 'unit' => 'kg'],
                    ['name' => 'Sfecla rosie murata', 'quantity_per_person' => 0.1, 'unit' => 'kg'],
                ],
                'packaging_cost' => 1.15,
            ],
            [
                'name' => 'Ostropel cremos de pui cu orez simplu',
                'instructions' => 'Pastreaza sosul si orezul calde pana la ambalare.',
                'ingredients' => [
                    ['name' => 'Pulpe de pui', 'quantity_per_person' => 0.15, 'unit' => 'kg'],
                    ['name' => 'Sos de rosii si usturoi', 'quantity_per_person' => 0.07, 'unit' => 'kg'],
                    ['name' => 'Orez', 'quantity_per_person' => 0.08, 'unit' => 'kg'],
                ],
                'packaging_cost' => 1.15,
            ],
            [
                'name' => 'Varza a la Cluj',
                'instructions' => 'Asaza ingredientele in straturi si serveste cu smantana.',
                'ingredients' => [
                    ['name' => 'Carne tocata', 'quantity_per_person' => 0.12, 'unit' => 'kg'],
                    ['name' => 'Varza tocata', 'quantity_per_person' => 0.2, 'unit' => 'kg'],
                    ['name' => 'Orez', 'quantity_per_person' => 0.05, 'unit' => 'kg'],
                    ['name' => 'Smantana', 'quantity_per_person' => 0.03, 'unit' => 'kg'],
                ],
                'packaging_cost' => 1.2,
            ],
            [
                'name' => 'Iahnie de fasole cu afumatura si gogonele',
                'instructions' => 'Fierbe fasolea din timp si serveste cu gogonele.',
                'ingredients' => [
                    ['name' => 'Fasole boabe uscata', 'quantity_per_person' => 0.09, 'unit' => 'kg'],
                    ['name' => 'Ciolan afumat dezosat', 'quantity_per_person' => 0.08, 'unit' => 'kg'],
                    ['name' => 'Gogonele', 'quantity_per_person' => 0.1, 'unit' => 'kg'],
                ],
                'packaging_cost' => 1.15,
            ],
            [
                'name' => 'Papricas de pui cu galuste de faina',
                'instructions' => 'Galustele se pregatesc proaspete; serveste imediat.',
                'ingredients' => [
                    ['name' => 'Carne de pui', 'quantity_per_person' => 0.15, 'unit' => 'kg'],
                    ['name' => 'Faina si oua', 'quantity_per_person' => 0.06, 'unit' => 'kg'],
                    ['name' => 'Smantana de gatit', 'quantity_per_person' => 0.03, 'unit' => 'kg'],
                ],
                'packaging_cost' => 1.15,
            ],
            [
                'name' => 'Tocana de ciuperci cu pui si mamaliga',
                'instructions' => 'Serveste tocanita calda cu mamaliga.',
                'ingredients' => [
                    ['name' => 'Ciuperci champignon', 'quantity_per_person' => 0.1, 'unit' => 'kg'],
                    ['name' => 'Piept sau pulpe de pui', 'quantity_per_person' => 0.1, 'unit' => 'kg'],
                    ['name' => 'Malai', 'quantity_per_person' => 0.08, 'unit' => 'kg'],
                ],
                'packaging_cost' => 1.15,
            ],
        ])->map(fn (array $menu) => Menu::updateOrCreate(['name' => $menu['name']], $menu + [
            'type' => 'main',
            'is_active' => true,
        ]));

        $soup = Menu::updateOrCreate(
            ['name' => 'Ciorba de legume'],
            [
                'type' => 'soup',
                'instructions' => 'Serveste ciorba calda in recipiente separate.',
                'ingredients' => [
                    ['name' => 'Mix legume pentru ciorba', 'quantity_per_person' => 0.2, 'unit' => 'kg'],
                    ['name' => 'Bors', 'quantity_per_person' => 0.05, 'unit' => 'l'],
                ],
                'packaging_cost' => 0,
                'is_active' => true,
            ],
        );

        $additionalSoups = collect([
            [
                'name' => 'Ciorba de perisoare',
                'instructions' => 'Serveste ciorba calda in recipiente separate.',
                'ingredients' => [
                    ['name' => 'Carne tocata pentru perisoare', 'quantity_per_person' => 0.07, 'unit' => 'kg'],
                    ['name' => 'Legume pentru ciorba', 'quantity_per_person' => 0.15, 'unit' => 'kg'],
                    ['name' => 'Orez', 'quantity_per_person' => 0.02, 'unit' => 'kg'],
                    ['name' => 'Bors', 'quantity_per_person' => 0.05, 'unit' => 'l'],
                ],
            ],
            [
                'name' => 'Ciorba a la grec',
                'instructions' => 'Adauga smantana si lamaie la final, inainte de portionare.',
                'ingredients' => [
                    ['name' => 'Carne de pui', 'quantity_per_person' => 0.1, 'unit' => 'kg'],
                    ['name' => 'Legume pentru ciorba', 'quantity_per_person' => 0.15, 'unit' => 'kg'],
                    ['name' => 'Orez', 'quantity_per_person' => 0.02, 'unit' => 'kg'],
                    ['name' => 'Smantana', 'quantity_per_person' => 0.03, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Supa cu galuste',
                'instructions' => 'Galustele se pregatesc proaspete si se adauga inainte de servire.',
                'ingredients' => [
                    ['name' => 'Carne de pui', 'quantity_per_person' => 0.08, 'unit' => 'kg'],
                    ['name' => 'Legume pentru supa', 'quantity_per_person' => 0.15, 'unit' => 'kg'],
                    ['name' => 'Faina si oua', 'quantity_per_person' => 0.04, 'unit' => 'kg'],
                ],
            ],
        ])->map(fn (array $soupData) => Menu::updateOrCreate(
            ['name' => $soupData['name']],
            $soupData + ['type' => 'soup', 'packaging_cost' => 0, 'is_active' => true],
        ));

        collect([
            'Penne Bolognese la cuptor cu cascaval' => ['Gluten', 'Lapte'],
            'Mazare scazuta cu piept de pui si paine' => ['Gluten'],
            'Varza a la Cluj' => ['Lapte'],
            'Papricas de pui cu galuste de faina' => ['Gluten', 'Oua', 'Lapte'],
            'Ciorba de legume' => ['Telina'],
            'Ciorba de perisoare' => ['Gluten', 'Oua', 'Telina'],
            'Ciorba a la grec' => ['Lapte', 'Telina'],
            'Supa cu galuste' => ['Gluten', 'Oua', 'Telina'],
        ])->each(fn (array $allergens, string $menuName) => Menu::where('name', $menuName)->update(['allergens' => $allergens]));

        $congregations->each(fn (Congregation $congregation) => $congregation->menus()
            ->syncWithoutDetaching($menus->pluck('id')->push($soup->id)->merge($additionalSoups->pluck('id'))->all()));

        $firstMealDate = CarbonImmutable::parse('2026-11-28');
        $finalWeekAssignments = [0, 0, 1, 1, 2];
        $soups = collect([$soup])->merge($additionalSoups)->values();

        foreach (range(1, 16) as $weekNumber) {
            $weekStartDate = $firstMealDate->addWeeks($weekNumber - 1);
            $weeklyCongregation = $congregations[($weekNumber - 1) % $congregations->count()];
            $weeklySoup = $soups[($weekNumber - 1) % $soups->count()];
            $week = Week::updateOrCreate(
                ['week_number' => $weekNumber],
                [
                    'start_date' => $weekStartDate->toDateString(),
                    'congregation_id' => $weeklyCongregation->id,
                ],
            );

            foreach (range(0, 4) as $dayIndex) {
                $mealDate = $weekStartDate->addDays($dayIndex);
                $dailyCongregation = $weekNumber === 16
                    ? $congregations[$finalWeekAssignments[$dayIndex]]
                    : $weeklyCongregation;

                DailyMeal::updateOrCreate(
                    ['meal_date' => $mealDate],
                    [
                        'week_id' => $week->id,
                        'congregation_id' => $dailyCongregation->id,
                        'menu_id' => $menus[($weekNumber + $dayIndex - 1) % $menus->count()]->id,
                        'soup_menu_id' => $dayIndex === 0 ? $weeklySoup->id : null,
                        'estimated_people' => 0,
                        'status' => 'draft',
                    ],
                );
            }
        }

        Menu::whereIn('name', [
            'Meniu pilot - Tocanita',
            'Meniu pilot - Orez cu legume',
            'Meniu pilot - Paste',
        ])->delete();
    }
}