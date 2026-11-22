<?php

namespace App\Http\Controllers;

use App\Models\PublicCongregationWeekLink;
use App\Services\CongregationScheduleReport;
use App\Services\WeeklyShoppingListCalculator;
use Illuminate\Http\Response;

class PublicCongregationWeekSheetController extends Controller
{
    public function __invoke(
        PublicCongregationWeekLink $publicCongregationWeekLink,
        WeeklyShoppingListCalculator $calculator,
        CongregationScheduleReport $scheduleReport,
    ): Response {
        $publicCongregationWeekLink->loadMissing('congregation');
        $congregation = $publicCongregationWeekLink->congregation;
        $week = $scheduleReport->build($congregation);
        $shoppingList = $calculator->calculate($week);

        $days = $week->dailyMeals->map(fn ($meal): array => [
            'date' => $meal->meal_date->translatedFormat('D, j M'),
            'main' => $meal->menu?->name ?? '— fără meniu —',
            'soup' => $meal->soupMenu?->name ?? '-',
            'dessert' => $meal->dessertMenu?->name ?? '-',
            'allergens' => $meal->menu?->allergens,
            'portions' => $meal->estimated_people,
        ])->all();

        $ingredients = array_map(
            fn (array $ingredient): array => [
                'name' => (string) $ingredient['name'],
                'quantity' => (float) $ingredient['quantity'],
                'unit' => (string) $ingredient['unit'],
            ],
            $shoppingList['ingredients'],
        );

        return response()->view('public-congregation-week-sheet', [
            'congregationName' => $congregation->name,
            'periodLabel' => $week->periodLabel(),
            'days' => $days,
            'ingredients' => $ingredients,
            'incompleteMeals' => (int) $shoppingList['incomplete_meals'],
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
