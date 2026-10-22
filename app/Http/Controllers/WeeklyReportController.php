<?php

namespace App\Http\Controllers;

use App\Models\Week;
use App\Services\WeeklyDaySheets;
use App\Services\WeeklyShoppingListCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class WeeklyReportController extends Controller
{
    public function __invoke(
        Week $week,
        WeeklyShoppingListCalculator $calculator,
        WeeklyDaySheets $daySheets,
    ): View {
        Gate::authorize('view', $week);

        $week->load([
            'congregation',
            'dailyMeals' => fn ($query) => $query->orderBy('meal_date'),
            'dailyMeals.menu',
            'dailyMeals.soupMenu',
            'dailyMeals.dessertMenu',
            'dailyMeals.congregation',
        ]);

        $sheets = $daySheets->for($week);

        return view('weekly-report', [
            'week' => $week,
            'shoppingList' => $calculator->calculate($week),
            'daySheets' => $sheets,
            'averageCostPerPortion' => $this->averageCostPerPortion($sheets),
        ]);
    }

    private function averageCostPerPortion(Collection $daySheets): ?float
    {
        $pricedSheets = $daySheets->filter(
            fn (array $sheet): bool => ! $sheet['cost']['has_missing_prices'] && $sheet['portions'] > 0,
        );
        $totalPortions = (float) $pricedSheets->sum('portions');

        if ($totalPortions <= 0) {
            return null;
        }

        return round($pricedSheets->sum(fn (array $sheet): float => $sheet['cost']['total_cost']) / $totalPortions, 2);
    }
}
