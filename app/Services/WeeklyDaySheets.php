<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Week;
use Illuminate\Support\Collection;

class WeeklyDaySheets
{
    public function __construct(
        private DailyMealComponents $components,
        private DailyMealCostCalculator $costCalculator,
    ) {}

    /**
     * Fisele pe zi ale saptamanii (componente, portii, costuri).
     *
     * Acelasi calcul este folosit de raportul saptamanal si de raportul PDF
     * al planului saptamanii, ca sa nu existe doua formule de cost.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function for(Week $week): Collection
    {
        $week->loadMissing([
            'dailyMeals' => fn ($query) => $query->orderBy('meal_date'),
            'dailyMeals.menu',
            'dailyMeals.soupMenu',
            'dailyMeals.dessertMenu',
            'dailyMeals.congregation',
        ]);

        return $week->dailyMeals
            ->map(fn (DailyMeal $dailyMeal): array => $this->sheet($dailyMeal))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function sheet(DailyMeal $dailyMeal): array
    {
        $dayComponents = collect($this->components->for($dailyMeal))
            ->map(fn (array $component, string $key): array => $component + [
                'key' => $key,
                'label' => match ($key) {
                    'main' => 'Fel principal',
                    'soup' => 'Ciorbă',
                    'dessert' => 'Desert / gustare',
                },
            ])
            ->filter(fn (array $component): bool => $component['menu'] !== null)
            ->values()
            ->all();

        $cost = $this->costCalculator->calculate($dailyMeal);
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
    }
}
