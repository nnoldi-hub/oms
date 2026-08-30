<?php

use App\Http\Controllers\PublicDailyMealController;
use App\Http\Controllers\CongregationWeeklyReportController;
use App\Http\Controllers\WeeklyReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mese/{dailyMeal:public_token}', PublicDailyMealController::class)
    ->name('public-daily-meals.show');

Route::middleware('auth')->get('/rapoarte/saptamani/{week}', WeeklyReportController::class)
    ->name('weekly-reports.show');

Route::middleware('auth')->get('/rapoarte/saptamani/{week}/congregatii/{congregation}', CongregationWeeklyReportController::class)
    ->name('congregation-weekly-reports.show');
