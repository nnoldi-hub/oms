<?php

namespace App\Filament\Pages;

use App\Models\DailyMeal;
use App\Models\Week;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class CalendarSaptamanal extends Page
{
    protected string $view = 'filament.pages.calendar-saptamanal';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Calendar saptamanal';

    protected static string|\UnitEnum|null $navigationGroup = 'Planificare';

    protected static ?int $navigationSort = 1;

    public ?int $weekId = null;

    /** @var array<int, int> */
    public array $estimatedPeople = [];

    public function mount(): void
    {
        $this->weekId = $this->weeks->first()?->id;
        $this->loadEstimatedPeople();
    }

    public function updatedWeekId(): void
    {
        $this->loadEstimatedPeople();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Week::class) ?? false;
    }

    public function getWeeksProperty(): Collection
    {
        $query = Week::query()->orderBy('week_number');
        $user = auth()->user();

        if ($user?->isCoordinator()) {
            $query->where(function (Builder $scopedQuery) use ($user): void {
                $scopedQuery
                    ->where('congregation_id', $user->congregation_id)
                    ->orWhereHas('dailyMeals', fn (Builder $mealQuery) => $mealQuery->where('congregation_id', $user->congregation_id));
            });
        }

        return $query->get();
    }

    public function getSelectedWeekProperty(): ?Week
    {
        $week = $this->weeks->firstWhere('id', $this->weekId);

        return $week?->load([
            'dailyMeals' => fn (HasMany $query) => $query->orderBy('meal_date'),
            'dailyMeals.congregation',
            'dailyMeals.menu',
            'dailyMeals.soupMenu',
        ]);
    }

    public function saveEstimatedPeople(int $mealId): void
    {
        $this->validate([
            "estimatedPeople.{$mealId}" => ['required', 'integer', 'min:0', 'max:5000'],
        ]);

        $dailyMeal = DailyMeal::query()
            ->whereKey($mealId)
            ->where('week_id', $this->weekId)
            ->firstOrFail();

        Gate::authorize('update', $dailyMeal);
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isConstructionTeam(), 403);

        $dailyMeal->update(['estimated_people' => (int) $this->estimatedPeople[$mealId]]);

        Notification::make()
            ->title('Numarul de portii a fost actualizat.')
            ->success()
            ->send();
    }

    private function loadEstimatedPeople(): void
    {
        $this->estimatedPeople = $this->selectedWeek?->dailyMeals
            ->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $dailyMeal->estimated_people])
            ->all() ?? [];
    }
}
