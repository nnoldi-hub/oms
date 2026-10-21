<?php

namespace App\Http\Controllers;

use App\Models\DailyMeal;
use App\Models\Week;
use App\Services\DailyMealComponents;
use App\Services\DailyMealCostCalculator;
use App\Services\WeeklyShoppingListCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class WeeklyReportController extends Controller
{
    public function __invoke(
        Week $week,
        WeeklyShoppingListCalculator $calculator,
        DailyMealComponents $components,
        DailyMealCostCalculator $costCalculator,
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

        $daySheets = $this->daySheets($week, $components, $costCalculator);

        return view('weekly-report', [
            'week' => $week,
            'shoppingList' => $calculator->calculate($week),
            'daySheets' => $daySheets,
            'averageCostPerPortion' => $this->averageCostPerPortion($daySheets),
        ]);
    }

    private function daySheets(Week $week, DailyMealComponents $components, DailyMealCostCalculator $costCalculator): Collection
    {
        return $week->dailyMeals->map(function (DailyMeal $dailyMeal) use ($components, $costCalculator): array {
            $dayComponents = collect($components->for($dailyMeal))
                ->map(fn (array $component, string $key): array => $component + [
                    'key' => $key,
                    'label' => match ($key) {
                        'main' => 'Fel principal',
                        'soup' => 'Ciorba',
                        'dessert' => 'Desert / gustare',
                    },
                ])
                ->filter(fn (array $component): bool => $component['menu'] !== null)
                ->values()
                ->all();
            $cost = $costCalculator->calculate($dailyMeal);
            $portions = $dailyMeal->portions();
            $ingredientsCost = round(collect($dayComponents)->sum(
                fn (array $component): float => (float) $component['requirements']['totals']['ingredients_cost'],
            ), 2);
            $packagingCost = round(collect($dayComponents)->sum(
                fn (array $component): float => (float) $component['requirements']['packaging']['total_cost'],
            ), 2);

            return [
                'meal' => $dailyMeal,
                'components' => $dayComponents,
                'portions' => $portions,
                'portions_label' => $dailyMeal->confirmed_people !== null ? 'Portii confirmate' : 'Portii estimate',
                'cost' => $cost,
                'ingredients_cost' => $ingredientsCost,
                'packaging_cost' => $packagingCost,
                'cost_per_portion' => $portions > 0 && ! $cost['has_missing_prices']
                    ? round($cost['total_cost'] / $portions, 2)
                    : null,
            ];
        })->values();
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
