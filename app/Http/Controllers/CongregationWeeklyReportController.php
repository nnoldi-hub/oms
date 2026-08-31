<?php

namespace App\Http\Controllers;

use App\Models\Congregation;
use App\Models\Week;
use App\Services\WeeklyShoppingListCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class CongregationWeeklyReportController extends Controller
{
    public function __invoke(Week $week, Congregation $congregation, WeeklyShoppingListCalculator $calculator): View
    {
        Gate::authorize('view', $week);

        abort_unless(
            auth()->user()?->isAdmin() || auth()->user()?->congregation_id === $congregation->id,
            403,
        );

        $week->load([
            'dailyMeals' => fn ($query) => $query->where('congregation_id', $congregation->id)->orderBy('meal_date'),
            'dailyMeals.menu',
            'dailyMeals.soupMenu',
            'dailyMeals.dessertMenu',
            'dailyMeals.congregation',
        ]);

        return view('congregation-weekly-report', [
            'week' => $week,
            'congregation' => $congregation,
            'shoppingList' => $calculator->calculate($week),
        ]);
    }
}