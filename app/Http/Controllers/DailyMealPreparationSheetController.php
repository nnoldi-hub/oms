<?php

namespace App\Http\Controllers;

use App\Models\DailyMeal;
use App\Services\DailyMealCostCalculator;
use App\Services\MealRequirementCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class DailyMealPreparationSheetController extends Controller
{
    public function __invoke(DailyMeal $dailyMeal, MealRequirementCalculator $calculator, DailyMealCostCalculator $costCalculator): View
    {
        Gate::authorize('view', $dailyMeal);

        $dailyMeal->load(['congregation', 'menu', 'soupMenu']);

        return view('daily-meal-preparation-sheet', [
            'dailyMeal' => $dailyMeal,
            'mainRequirements' => $dailyMeal->menu === null ? null : $calculator->calculate($dailyMeal),
            'soupRequirements' => $dailyMeal->soupMenu === null ? null : $calculator->calculate(
                (clone $dailyMeal)->setRelation('menu', $dailyMeal->soupMenu),
            ),
            'dailyCost' => $costCalculator->calculate($dailyMeal),
        ]);
    }
}