<?php

namespace App\Http\Controllers;

use App\Models\DailyMeal;
use App\Services\DailyMealComponents;
use App\Services\DailyMealCostCalculator;
use App\Services\DailyMessageBuilder;
use App\Services\DailyShoppingListCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class DailyMealPreparationSheetController extends Controller
{
    public function __invoke(
        DailyMeal $dailyMeal,
        DailyMealComponents $components,
        DailyMealCostCalculator $costCalculator,
        DailyShoppingListCalculator $shoppingListCalculator,
        DailyMessageBuilder $messageBuilder,
    ): View {
        Gate::authorize('view', $dailyMeal);

        $dailyMeal->load(['congregation', 'menu', 'soupMenu', 'dessertMenu', 'volunteers']);

        $roles = collect($messageBuilder->rolesFor($dailyMeal))
            ->map(fn (array $people, string $role): array => [
                'label' => DailyMessageBuilder::roleLabel($role),
                'people' => $people,
            ])
            ->values()
            ->all();

        return view('daily-meal-preparation-sheet', [
            'dailyMeal' => $dailyMeal,
            'components' => $components->for($dailyMeal),
            'dailyCost' => $costCalculator->calculate($dailyMeal),
            'shoppingList' => $shoppingListCalculator->for($dailyMeal),
            'roles' => $roles,
            'uncoveredRoles' => collect($messageBuilder->uncoveredRoleList($dailyMeal))
                ->map(fn (string $role): string => DailyMessageBuilder::roleLabel($role))
                ->all(),
        ]);
    }
}
