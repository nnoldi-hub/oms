<?php

namespace App\Filament\Pages;

use App\Models\DailyMeal;
use App\Models\FoodFundTransaction;
use App\Models\SupplyItem;
use App\Models\Week;
use App\Services\DailyMealCostCalculator;
use App\Services\DonationFundService;
use App\Services\DonationSummaryService;
use App\Support\CongregationAccess;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

        return ($user?->canManageCongregationFunds() ?? false) || ($user?->isProjectSupervisor() ?? false);
    }

    public function mount(): void
    {
        $this->weekId = $this->weeksQuery()->orderByDesc('start_date')->value('id');
    }

    /**
     * Coordonatorul vede doar saptamanile si zilele congregatiei sale.
     */
    private function congregationScope(): ?int
    {
        $user = auth()->user();

        return $user?->isScopedToCongregation() ? (int) $user->congregation_id : null;
    }

    private function weeksQuery(): Builder
    {
        $congregationId = $this->congregationScope();

        return Week::query()
            ->when($congregationId !== null, fn (Builder $query) => CongregationAccess::scopeWeeks($query, auth()->user()))
            ->with($this->mealRelations());
    }

    /**
     * @return array<string, \Closure|string>
     */
    private function mealRelations(): array
    {
        $congregationId = $this->congregationScope();

        return [
            'dailyMeals' => fn (HasMany $query) => $query->when(
                $congregationId !== null,
                fn ($meals) => CongregationAccess::scopeMealsRelation($meals, auth()->user()),
            ),
            'dailyMeals.menu',
            'dailyMeals.soupMenu',
            'dailyMeals.dessertMenu',
        ];
    }

    public function getCanSeeStockProperty(): bool
    {
        return $this->congregationScope() === null;
    }

    public function getWeekProperty(): ?Week
    {
        return $this->weeksQuery()->find($this->weekId);
    }

    public function getWeeksProperty(): Collection
    {
        return $this->weeksQuery()->orderBy('week_number')->get();
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

    /**
     * Acoperirea prin donatii a costului estimat al saptamanii selectate.
     * Nu contine date personale ale donatorilor si respecta scope-ul de congregatie.
     *
     * @return array{received: float, promised: float, total: float, items: int, estimated_cost: float, net_cost: float, remaining: float, coverage_percent: float}
     */
    public function getDonationsProperty(DonationSummaryService $summary): array
    {
        $estimatedCost = (float) $this->costs->sum('cost');
        $week = $this->week;

        if ($week === null) {
            return [
                'received' => 0.0,
                'promised' => 0.0,
                'total' => 0.0,
                'items' => 0,
                'estimated_cost' => round($estimatedCost, 2),
                'net_cost' => round($estimatedCost, 2),
                'remaining' => round($estimatedCost, 2),
                'coverage_percent' => 0.0,
            ];
        }

        return $summary->coverage($estimatedCost, $summary->forWeek($week));
    }

    /**
     * @return array{income: float, expense: float, balance: float, transactions: list<array<string, mixed>>, donations: list<array<string, mixed>>}
     */
    public function getFundProperty(DonationFundService $service): array
    {
        return $service->statement();
    }

    public function getSupplyCostProperty(): float
    {
        return (float) SupplyItem::query()->where('is_active', true)->get()->sum(
            fn (SupplyItem $item): float => (float) $item->current_stock * (float) $item->unit_cost,
        );
    }

    public function getWeekIsClosedProperty(): bool
    {
        $week = $this->week;

        return $week !== null && $week->start_date->copy()->addDays(4)->isPast();
    }

    public function getSpentThisWeekProperty(): float
    {
        $week = $this->week;

        if ($week === null) {
            return 0.0;
        }

        return (float) FoodFundTransaction::query()
            ->visibleTo(auth()->user())
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                $week->start_date->toDateString(),
                $week->start_date->copy()->addDays(4)->toDateString(),
            ])
            ->sum('amount');
    }

    /**
     * @return array{weeks: list<array<string, mixed>>, projected_total: float, completed_weeks: int, total_weeks: int, completed_average: float, recorded_expenses: float}
     */
    public function getBudgetProperty(DailyMealCostCalculator $calculator): array
    {
        $weeks = $this->weeksQuery()
            ->orderBy('week_number')
            ->get();

        $expensesByPeriod = FoodFundTransaction::query()
            ->visibleTo(auth()->user())
            ->where('type', 'expense')
            ->get()
            ->groupBy(fn (FoodFundTransaction $transaction): string => CarbonImmutable::parse($transaction->transaction_date)->toDateString())
            ->map(fn (Collection $transactions): float => (float) $transactions->sum('amount'));

        $today = CarbonImmutable::now()->startOfDay();
        $weekBudgets = [];
        $estimatedTotal = 0.0;
        $completedEstimated = 0.0;
        $completedCount = 0;

        foreach ($weeks as $week) {
            $start = CarbonImmutable::parse($week->start_date);
            $end = $start->addDays(4);
            $estimated = (float) $week->dailyMeals->sum(fn (DailyMeal $meal): float => $calculator->calculate($meal)['total_cost']);
            $spent = (float) $expensesByPeriod
                ->filter(fn (float $amount, string $date): bool => $date >= $start->toDateString() && $date <= $end->toDateString())
                ->sum();

            $closed = $end->isBefore($today);
            $estimatedTotal += $estimated;

            if ($closed) {
                $completedEstimated += $estimated;
                $completedCount++;
            }

            $weekBudgets[] = [
                'week_number' => $week->week_number,
                'start' => $start,
                'end' => $end,
                'estimated' => $estimated,
                'spent' => $spent,
                'difference' => $estimated - $spent,
                'closed' => $closed,
                'over_budget' => $closed && $spent > $estimated,
            ];
        }

        $recordedExpenses = (float) FoodFundTransaction::query()->visibleTo(auth()->user())->where('type', 'expense')->sum('amount');
        $totalWeeks = $weeks->count();
        $averagePerWeek = $completedCount > 0 ? $completedEstimated / $completedCount : ($totalWeeks > 0 ? $estimatedTotal / $totalWeeks : 0.0);

        return [
            'weeks' => $weekBudgets,
            'projected_total' => $estimatedTotal,
            'completed_weeks' => $completedCount,
            'total_weeks' => $totalWeeks,
            'completed_average' => $averagePerWeek,
            'recorded_expenses' => $recordedExpenses,
        ];
    }
}
