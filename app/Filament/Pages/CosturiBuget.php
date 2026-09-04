<?php

namespace App\Filament\Pages;

use App\Models\DailyMeal;
use App\Models\SupplyItem;
use App\Models\Week;
use App\Services\DailyMealCostCalculator;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class CosturiBuget extends Page
{
    protected string $view = 'filament.pages.costuri-buget';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static ?string $navigationLabel = 'Costuri & buget';
    protected static string|\UnitEnum|null $navigationGroup = 'Aprovizionare';
    protected static ?int $navigationSort = 4;

    public ?int $weekId = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->canManageSupply() ?? false) || ($user?->isProjectSupervisor() ?? false);
    }

    public function mount(): void
    {
        $this->weekId = Week::query()->orderByDesc('start_date')->value('id');
    }

    public function getWeekProperty(): ?Week
    {
        return Week::with(['dailyMeals.menu', 'dailyMeals.soupMenu', 'dailyMeals.dessertMenu'])->find($this->weekId);
    }

    public function getCostsProperty(DailyMealCostCalculator $calculator): Collection
    {
        return $this->week?->dailyMeals->map(function (DailyMeal $meal) use ($calculator): array {
            $cost = $calculator->calculate($meal);

            return [
                'date' => $meal->meal_date,
                'people' => $meal->estimated_people,
                'cost' => $cost['total_cost'],
                'per_person' => $meal->estimated_people > 0 ? round($cost['total_cost'] / $meal->estimated_people, 2) : 0,
                'missing' => $cost['has_missing_prices'],
            ];
        }) ?? collect();
    }

    public function getSupplyCostProperty(): float
    {
        return (float) SupplyItem::query()->where('is_active', true)->get()->sum(
            fn (SupplyItem $item): float => (float) $item->current_stock * (float) $item->unit_cost,
        );
    }
}
