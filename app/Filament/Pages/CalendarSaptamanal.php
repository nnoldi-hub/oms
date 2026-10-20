<?php

namespace App\Filament\Pages;

use App\Models\DailyMeal;
use App\Models\SupplyContribution;
use App\Models\Week;
use App\Services\DailyMealCostCalculator;
use App\Services\MenuSchedulingValidator;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    /** @var array<int, string|null> */
    public array $maximumBudget = [];

    /** @var array<int, int|null> */
    public array $contributorCount = [];

    public ?int $moveDayMealId = null;

    public ?string $moveDayDate = null;

    public ?int $moveWeekId = null;

    public int $moveWeekDays = 1;

    public ?int $freeDayMealId = null;

    public bool $freeDayShiftRest = false;

    public function mount(): void
    {
        $this->weekId = $this->weeks->first()?->id;
        $this->loadCalendarValues();
    }

    public function updatedWeekId(): void
    {
        $this->loadCalendarValues();
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
            'dailyMeals' => fn (HasMany $query) => $this->scopeDailyMealsForUser($query)->orderBy('meal_date'),
            'dailyMeals.congregation',
            'dailyMeals.menu',
            'dailyMeals.soupMenu',
            'dailyMeals.dessertMenu',
        ]);
    }

    /**
     * @return array<string, list<array{icon: string, label: string}>>
     */
    public function getSupplyIndicatorsProperty(): array
    {
        $week = $this->selectedWeek;

        if ($week === null) {
            return [];
        }

        $query = SupplyContribution::query()
            ->whereBetween('delivery_date', [
                $week->start_date->toDateString(),
                $week->start_date->copy()->addDays(4)->toDateString(),
            ])
            ->with(['congregation', 'supplyItem']);

        if (auth()->user()?->isCoordinator()) {
            $query->where('congregation_id', auth()->user()->congregation_id);
        }

        return $query
            ->get()
            ->groupBy(fn (SupplyContribution $contribution): string => $contribution->delivery_date->toDateString())
            ->map(fn ($contributions): array => $this->buildSupplyIndicators($contributions))
            ->all();
    }

    private function scopeDailyMealsForUser(HasMany $query): HasMany
    {
        $user = auth()->user();

        if ($user?->isCoordinator()) {
            $query->where('congregation_id', $user->congregation_id);
        }

        return $query;
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

    public function saveBudget(int $mealId): void
    {
        $this->validate([
            "maximumBudget.{$mealId}" => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            "contributorCount.{$mealId}" => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $dailyMeal = DailyMeal::query()->whereKey($mealId)->where('week_id', $this->weekId)->firstOrFail();

        Gate::authorize('update', $dailyMeal);
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isCoordinator(), 403);

        $dailyMeal->update([
            'maximum_budget' => blank($this->maximumBudget[$mealId] ?? null) ? null : $this->maximumBudget[$mealId],
            'contributor_count' => blank($this->contributorCount[$mealId] ?? null) ? null : $this->contributorCount[$mealId],
        ]);

        Notification::make()->title('Bugetul zilei a fost actualizat.')->success()->send();
    }

    public function openMoveDay(int $mealId): void
    {
        $meal = $this->calendarMeal($mealId);

        $this->moveDayMealId = $meal->id;
        $this->moveDayDate = $meal->meal_date->toDateString();
    }

    public function moveDay(): void
    {
        $this->validate([
            'moveDayDate' => ['required', 'date'],
        ]);

        $meal = $this->calendarMeal((int) $this->moveDayMealId);

        $newDate = CarbonImmutable::parse($this->moveDayDate)->toDateString();

        if ($newDate === $meal->meal_date->toDateString()) {
            $this->closeSchedulingAction();

            return;
        }

        $conflict = DailyMeal::query()
            ->whereDate('meal_date', $newDate)
            ->whereKeyNot($meal->id)
            ->exists();

        if ($conflict) {
            Notification::make()
                ->title('In acea zi exista deja o masa programata.')
                ->danger()
                ->send();

            return;
        }

        $warnings = $this->previewWarnings($meal, $newDate);

        $meal->meal_date = $newDate;
        $meal->save();

        $this->closeSchedulingAction();
        $this->loadCalendarValues();
        $this->notifyWarnings($warnings, 'Ziua a fost mutata. Retetele au ramas neschimbate.');
    }

    public function openMoveWeek(int $weekId): void
    {
        $this->moveWeekId = $weekId;
        $this->moveWeekDays = 1;
    }

    public function moveWeek(): void
    {
        $this->validate([
            'moveWeekDays' => ['required', 'integer', 'min:-30', 'max:30'],
        ]);

        $week = Week::query()->findOrFail((int) $this->moveWeekId);
        $meals = $week->dailyMeals()->orderBy('meal_date')->get();

        $targetDates = $meals->map(
            fn (DailyMeal $meal): string => $meal->meal_date->addDays($this->moveWeekDays)->toDateString(),
        );

        $conflict = DailyMeal::query()
            ->whereIn(DB::raw('date(meal_date)'), $targetDates->all())
            ->whereNotIn('id', $meals->pluck('id'))
            ->exists();

        if ($conflict) {
            Notification::make()
                ->title('Mutarea intra in conflict cu alte zile de masa existente.')
                ->danger()
                ->send();

            return;
        }

        DB::transaction(function () use ($meals, $targetDates): void {
            $sorted = $meals
                ->sortByDesc(fn (DailyMeal $meal): string => $meal->meal_date->toDateString())
                ->values();

            foreach ($sorted as $meal) {
                $offset = $meals->search(fn (DailyMeal $candidate): bool => $candidate->id === $meal->id);
                $meal->meal_date = $targetDates[$offset];
                $meal->save();
            }

            $week = $meals->first()->week;
            $week->start_date = $week->start_date->addDays($this->moveWeekDays);
            $week->save();
        });

        $this->closeSchedulingAction();
        $this->loadCalendarValues();

        Notification::make()
            ->title('Saptamana a fost mutata cu '.$this->moveWeekDays.' zile.')
            ->success()
            ->send();
    }

    public function openFreeDay(int $mealId): void
    {
        $meal = $this->calendarMeal($mealId);

        $this->freeDayMealId = $meal->id;
        $this->freeDayShiftRest = false;
    }

    public function markFreeDay(): void
    {
        $meal = $this->calendarMeal((int) $this->freeDayMealId);

        DB::transaction(function () use ($meal): void {
            $meal->is_free_day = true;
            $meal->menu_id = null;
            $meal->soup_menu_id = null;
            $meal->dessert_menu_id = null;
            $meal->save();

            if ($this->freeDayShiftRest) {
                $following = DailyMeal::query()
                    ->where('week_id', $meal->week_id)
                    ->whereDate('meal_date', '>', $meal->meal_date->toDateString())
                    ->orderBy('meal_date')
                    ->get();

                foreach ($following as $next) {
                    $next->meal_date = $next->meal_date->addDay();
                    $next->save();
                }
            }
        });

        $this->closeSchedulingAction();
        $this->loadCalendarValues();

        Notification::make()
            ->title($this->freeDayShiftRest
                ? 'Ziua a fost marcata libera, iar restul programului a fost mutat cu o zi.'
                : 'Ziua a fost marcata libera; restul programului a ramas neschimbat.')
            ->success()
            ->send();
    }

    public function closeSchedulingAction(): void
    {
        $this->moveDayMealId = null;
        $this->moveDayDate = null;
        $this->moveWeekId = null;
        $this->freeDayMealId = null;
        $this->freeDayShiftRest = false;
    }

    private function calendarMeal(int $mealId): DailyMeal
    {
        $meal = DailyMeal::query()
            ->whereKey($mealId)
            ->where('week_id', $this->weekId)
            ->firstOrFail();

        Gate::authorize('update', $meal);
        abort_unless(auth()->user()?->isAdmin(), 403);

        return $meal;
    }

    /**
     * @return array<int, string>
     */
    private function previewWarnings(DailyMeal $meal, string $newDate): array
    {
        $probe = $meal->replicate(['id']);
        $probe->meal_date = $newDate;

        return app(MenuSchedulingValidator::class)->warnings($probe);
    }

    /**
     * @param  array<int, string>  $warnings
     */
    private function notifyWarnings(array $warnings, string $title): void
    {
        $notification = Notification::make()->title($title)->success();

        if ($warnings !== []) {
            $notification
                ->title($title)
                ->body(implode(' ', $warnings))
                ->warning();
        }

        $notification->send();
    }

    /** @return array<int, array{total_cost: float, has_missing_prices: bool}> */
    public function getDailyCostsProperty(DailyMealCostCalculator $calculator): array    {
        return $this->selectedWeek?->dailyMeals
            ->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $calculator->calculate($dailyMeal)])
            ->all() ?? [];
    }

    private function loadCalendarValues(): void
    {
        $dailyMeals = $this->selectedWeek?->dailyMeals ?? collect();
        $this->estimatedPeople = $dailyMeals->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $dailyMeal->estimated_people])->all();
        $this->maximumBudget = $dailyMeals->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $dailyMeal->maximum_budget])->all();
        $this->contributorCount = $dailyMeals->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $dailyMeal->contributor_count])->all();
    }

    /**
     * @param Collection<int, SupplyContribution> $contributions
     * @return list<array{icon: string, label: string}>
     */
    private function buildSupplyIndicators(Collection $contributions): array
    {
        $indicators = [];

        foreach ([
            'snack' => ['icon' => '🍪', 'label' => 'Gustari'],
            'water' => ['icon' => '💧', 'label' => 'Apa'],
        ] as $category => $definition) {
            $categoryContributions = $contributions->filter(
                fn (SupplyContribution $contribution): bool => $contribution->supplyItem?->category === $category,
            );

            if ($categoryContributions->isEmpty()) {
                continue;
            }

            $details = $categoryContributions
                ->groupBy(fn (SupplyContribution $contribution): string => $contribution->congregation?->name ?? 'Congregatie nealocata')
                ->map(function (Collection $congregationContributions): string {
                    $name = $congregationContributions->first()->congregation?->name ?? 'Congregatie nealocata';
                    $quantity = (float) $congregationContributions->sum('quantity');
                    $unit = $congregationContributions->first()->supplyItem?->unit ?? 'buc';

                    return sprintf('%s (%s %s)', $name, rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.'), $unit);
                })
                ->implode(', ');

            $indicators[] = [
                'icon' => $definition['icon'],
                'label' => $definition['label'].': '.$details,
            ];
        }

        if ($contributions->isNotEmpty()) {
            $indicators[] = [
                'icon' => '👥',
                'label' => 'Congregatii participante: '.$contributions
                    ->pluck('congregation.name')
                    ->filter()
                    ->unique()
                    ->implode(', '),
            ];
        }

        return $indicators;
    }
}
