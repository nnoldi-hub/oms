<?php

namespace App\Http\Controllers;

use App\Models\PublicCongregationWeekLink;
use App\Services\WeeklyShoppingListCalculator;
use Illuminate\Contracts\View\View;

class PublicCongregationWeekSheetController extends Controller
{
    public function __invoke(PublicCongregationWeekLink $publicCongregationWeekLink, WeeklyShoppingListCalculator $calculator): View
    {
        $publicCongregationWeekLink->load(['week', 'congregation']);
        $week = $publicCongregationWeekLink->week;
        $congregation = $publicCongregationWeekLink->congregation;
        $week->load([
            'dailyMeals' => fn ($query) => $query->where('congregation_id', $congregation->id)->orderBy('meal_date'),
            'dailyMeals.menu',
            'dailyMeals.soupMenu',
            'dailyMeals.dessertMenu',
        ]);

        return view('public-congregation-week-sheet', [
            'week' => $week,
            'congregation' => $congregation,
            'shoppingList' => $calculator->calculate($week),
        ]);
    }
}