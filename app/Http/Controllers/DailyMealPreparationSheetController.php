<?php

namespace App\Http\Controllers;

use App\Models\DailyMeal;
use App\Services\DailyMealComponents;
use App\Services\DailyMealCostCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class DailyMealPreparationSheetController extends Controller
{
    public function __invoke(DailyMeal $dailyMeal, DailyMealComponents $components, DailyMealCostCalculator $costCalculator): View
    {
        Gate::authorize('view', $dailyMeal);

        $dailyMeal->load(['congregation', 'menu', 'soupMenu', 'dessertMenu']);

        return view('daily-meal-preparation-sheet', [
            'dailyMeal' => $dailyMeal,
            'components' => $components->for($dailyMeal),
            'dailyCost' => $costCalculator->calculate($dailyMeal),
        ]);
    }
}
