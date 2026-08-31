<?php

namespace App\Http\Controllers;

use App\Models\DailyMeal;
use App\Models\Menu;
use App\Services\MealRequirementCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class MenuCostReportController extends Controller
{
    public function __invoke(MealRequirementCalculator $calculator): View
    {
        Gate::authorize('viewAny', Menu::class);

        $menus = Menu::query()->orderBy('type')->orderBy('name')->get()->map(function (Menu $menu) use ($calculator): array {
            $meal = new DailyMeal(['estimated_people' => 1]);
            $meal->setRelation('menu', $menu);
            $requirements = $calculator->calculate($meal);

            return ['menu' => $menu, 'requirements' => $requirements];
        });

        return view('menu-cost-report', ['menus' => $menus]);
    }
}