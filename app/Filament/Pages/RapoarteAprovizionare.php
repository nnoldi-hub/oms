<?php

namespace App\Filament\Pages;

use App\Models\DailySupplyPlan;
use App\Models\SupplyContribution;
use App\Models\SupplyItem;
use App\Models\Week;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RapoarteAprovizionare extends Page
{
    protected string $view = 'filament.pages.rapoarte-aprovizionare';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Rapoarte aprovizionare';

    protected static string|\UnitEnum|null $navigationGroup = 'Aprovizionare';

    protected static ?int $navigationSort = 3;

    public string $period = 'day';

    public string $reportType = 'supplies';

    public string $selectedDate;

    public ?int $weekId = null;

    public function mount(): void
    {
        $this->selectedDate = today()->toDateString();
        $this->weekId = Week::query()->orderByDesc('start_date')->value('id');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->canManageContributions() ?? false)
            || ($user?->isProjectSupervisor() ?? false)
            || ($user?->isCoordinator() ?? false);
    }

    public function getPeriodDatesProperty(): array
    {
        if ($this->period === 'week' && ($week = Week::find($this->weekId))) {
            return [$week->start_date->copy(), $week->start_date->copy()->addDays(4)];
        }

        $date = Carbon::parse($this->selectedDate);

        return [$date->copy(), $date->copy()];
    }

    public function getPlansProperty(): Collection
    {
        [$from, $to] = $this->periodDates;

        return DailySupplyPlan::query()
            ->whereBetween('plan_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('plan_date')
            ->get();
    }

    public function getContributionsProperty(): Collection
    {
        [$from, $to] = $this->periodDates;

        return SupplyContribution::query()
            ->whereBetween('delivery_date', [$from->toDateString(), $to->toDateString()])
            ->with(['congregation', 'supplyItem'])
            ->orderBy('delivery_date')
            ->get();
    }

    public function getStockItemsProperty(): Collection
    {
        return SupplyItem::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get();
    }

    public function getTotalsProperty(): array
    {
        return [
            'people' => (int) $this->plans->sum('people_count'),
            'water_required' => (float) $this->plans->sum(fn (DailySupplyPlan $plan): float => (float) $plan->still_water_required + (float) $plan->mineral_water_required),
            'water_confirmed' => (float) $this->plans->sum(fn (DailySupplyPlan $plan): float => (float) $plan->still_water_confirmed + (float) $plan->mineral_water_confirmed),
            'snacks_required' => (float) $this->plans->sum('snacks_required'),
            'snacks_confirmed' => (float) $this->plans->sum('snacks_confirmed'),
            'stock_alerts' => $this->stockItems->filter->isBelowMinimum()->count(),
        ];
    }

    public function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');
    }
}
