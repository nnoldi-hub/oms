<?php

namespace App\Http\Controllers;

use App\Models\Week;
use App\Services\WeeklyShoppingListCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class WeeklyReportController extends Controller
{
    public function __invoke(Week $week, WeeklyShoppingListCalculator $calculator): View
    {
        Gate::authorize('view', $week);

        $week->load([
            'congregation',
            'dailyMeals' => fn ($query) => $query->orderBy('meal_date'),
            'dailyMeals.menu',
            'dailyMeals.soupMenu',
            'dailyMeals.congregation',
        ]);

        return view('weekly-report', [
            'week' => $week,
            'shoppingList' => $calculator->calculate($week),
        ]);
    }
}