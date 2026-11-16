<?php

namespace App\Filament\Pages;

use App\Models\Congregation;
use App\Models\MealChangeLog;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class ActivitateCongregatii extends Page
{
    protected string $view = 'filament.pages.activitate-congregatii';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Activitate congregatii';

    protected static string|\UnitEnum|null $navigationGroup = 'Rapoarte';

    protected static ?int $navigationSort = 6;

    public ?string $congregationId = null;

    public ?string $eventType = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<int, string>
     */
    public function getCongregationOptionsProperty(): array
    {
        return Congregation::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @return array<string, string>
     */
    public function getEventTypeOptionsProperty(): array
    {
        return [
            MealChangeLog::EVENT_DISH_CHANGED => 'Fel schimbat',
            MealChangeLog::EVENT_COOKED_DIFFERENT => 'Gatit alt fel',
            MealChangeLog::EVENT_RECIPE_CREATED => 'Reteta noua',
            MealChangeLog::EVENT_COST_DEVIATION => 'Cost real diferit',
        ];
    }

    /**
     * @return Collection<int, MealChangeLog>
     */
    public function getLogsProperty(): Collection
    {
        return MealChangeLog::query()
            ->with(['congregation', 'user'])
            ->when(filled($this->congregationId), fn ($query) => $query->where('congregation_id', $this->congregationId))
            ->when(filled($this->eventType), fn ($query) => $query->where('event_type', $this->eventType))
            ->when(filled($this->dateFrom), fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when(filled($this->dateTo), fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at')
            ->limit(300)
            ->get();
    }

    public function resetFilters(): void
    {
        $this->congregationId = null;
        $this->eventType = null;
        $this->dateFrom = null;
        $this->dateTo = null;
    }
}
