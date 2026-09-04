<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\DailySupplyPlan;
use App\Models\SupplyContribution;
use Illuminate\Support\Carbon;

class SupplyPlanningService
{
    public const WATER_LITERS_PER_PERSON = 0.5;
    public const SNACK_PORTIONS_PER_PERSON = 1.0;
    public const DESSERT_PORTIONS_PER_PERSON = 1.0;

    /**
     * @return array{people_count: int, water_liters: float, still_water_liters: float, mineral_water_liters: float, snacks_portions: float, desserts_portions: float}
     */
    public function calculateRequirements(int $peopleCount, float $mineralWaterRatio = 0.0): array
    {
        if ($peopleCount < 0 || $mineralWaterRatio < 0 || $mineralWaterRatio > 1) {
            throw new \InvalidArgumentException('Numarul de persoane si proportia apei trebuie sa fie valide.');
        }

        $water = $peopleCount * self::WATER_LITERS_PER_PERSON;

        return [
            'people_count' => $peopleCount,
            'water_liters' => round($water, 3),
            'still_water_liters' => round($water * (1 - $mineralWaterRatio), 3),
            'mineral_water_liters' => round($water * $mineralWaterRatio, 3),
            'snacks_portions' => round($peopleCount * self::SNACK_PORTIONS_PER_PERSON, 3),
            'desserts_portions' => round($peopleCount * self::DESSERT_PORTIONS_PER_PERSON, 3),
        ];
    }

    public function createOrUpdatePlan(Carbon|string $date, int $peopleCount, ?DailyMeal $dailyMeal = null, float $mineralWaterRatio = 0.0): DailySupplyPlan
    {
        $requirements = $this->calculateRequirements($peopleCount, $mineralWaterRatio);
        $date = Carbon::parse($date)->toDateString();
        $confirmed = $this->confirmedQuantities($date);

        return DailySupplyPlan::query()->updateOrCreate(
            ['plan_date' => $date],
            [
                'daily_meal_id' => $dailyMeal?->getKey(),
                'people_count' => $requirements['people_count'],
                'still_water_required' => $requirements['still_water_liters'],
                'mineral_water_required' => $requirements['mineral_water_liters'],
                'snacks_required' => $requirements['snacks_portions'],
                'desserts_required' => $requirements['desserts_portions'],
                'still_water_confirmed' => $confirmed['water'],
                'snacks_confirmed' => $confirmed['snacks'],
                'desserts_confirmed' => $confirmed['desserts'],
            ],
        );
    }

    public function confirmedQuantities(Carbon|string $date): array
    {
        $totals = SupplyContribution::query()
            ->whereDate('delivery_date', $date)
            ->whereIn('delivery_status', ['confirmed', 'in_transit', 'delivered'])
            ->with('supplyItem')
            ->get()
            ->groupBy(fn (SupplyContribution $contribution): string => $contribution->supplyItem->category)
            ->map(fn ($contributions): float => round((float) $contributions->sum('quantity'), 3));

        return [
            'water' => (float) ($totals['water'] ?? 0),
            'snacks' => (float) ($totals['snack'] ?? 0),
            'desserts' => (float) ($totals['dessert'] ?? 0),
            'auxiliary' => (float) ($totals['auxiliary'] ?? 0),
        ];
    }
}
