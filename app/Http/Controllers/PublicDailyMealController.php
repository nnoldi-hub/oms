<?php

namespace App\Http\Controllers;

use App\Models\DailyMeal;
use App\Services\MealRequirementCalculator;
use Illuminate\Contracts\View\View;

class PublicDailyMealController extends Controller
{
    public function __invoke(DailyMeal $dailyMeal, MealRequirementCalculator $calculator): View
    {
        abort_unless($dailyMeal->status === 'published', 404);

        $dailyMeal->load('menu', 'soupMenu');

        return view('public-daily-meal', [
            'dailyMeal' => $dailyMeal,
            'requirements' => $calculator->calculate($dailyMeal),
        ]);
    }
}