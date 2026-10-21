<?php

namespace App\Filament\Pages;

use App\Console\Commands\FillMenus;
use App\Models\DailyMeal;
use App\Models\FoodFundTransaction;
use App\Models\Menu;
use App\Models\SupplyContribution;
use App\Models\Volunteer;
use App\Models\Week;
use App\Models\WorkTeam;
use App\Services\DailyMealCostCalculator;
use App\Services\MenuSchedulingValidator;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
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

    /** @var array<string, bool> data (Y-m-d) => bifat */
    public array $workingDayDates = [];

    /** @var array<int, int|null> */
    public array $plannedMenus = [];

    /** @var array<int, int|null> */
    public array $plannedSoups = [];

    /** @var array<int, int|null> */
    public array $plannedDesserts = [];

    /** @var array<int, bool> */
    public array $cookedFlags = [];

    /** @var array<int, int|null> */
    public array $actualMenus = [];

    /** @var array<int, string|null> */
    public array $actualCosts = [];

    public ?int $newDishMealId = null;

    public ?string $newDishType = null;

    public string $newDishName = '';

    public ?int $suggestWeekId = null;

    public ?int $pendingWorkingDayMealId = null;

    public ?string $pendingWorkingDayDate = null;

    public bool $showCreateWeek = false;

    public ?int $newWeekNumber = null;

    public ?string $newWeekStartDate = null;

    public ?int $newWeekCongregationId = null;

    public ?int $pendingDeleteWeekId = null;

    public ?int $peopleMealId = null;

    public ?int $editingVolunteerId = null;

    public string $volunteerName = '';

    public string $volunteerPhone = '';

    public string $volunteerRole = '';

    public bool $volunteerHasAllergies = false;

    public string $volunteerAllergyDetails = '';

    public ?int $teamsMealId = null;

    public ?int $editingTeamId = null;

    public string $teamName = '';

    public ?int $teamMemberCount = null;

    public string $teamStartsAt = '';

    public string $teamEndsAt = '';

    public string $teamLocation = '';

    public ?string $teamSupplyResponsible = '';

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
            'dailyMeals.volunteers',
            'dailyMeals.workTeams',
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
        $this->pendingWorkingDayMealId = null;
        $this->pendingWorkingDayDate = null;
        $this->suggestWeekId = null;
        $this->closeNewDish();
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

    /** @return array<int, array{total_cost: float, has_missing_prices: bool, is_partial: bool}> */
    public function getDailyCostsProperty(DailyMealCostCalculator $calculator): array
    {
        return $this->selectedWeek?->dailyMeals
            ->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $calculator->calculate($dailyMeal)])
            ->all() ?? [];
    }

    /** @return array<int, array<int, string>> mealId => lista de avertismente */
    public function getCardWarningsProperty(): array
    {
        $validator = app(MenuSchedulingValidator::class);

        return $this->selectedWeek?->dailyMeals
            ->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $validator->warnings($dailyMeal)])
            ->all() ?? [];
    }

    private function loadCalendarValues(): void
    {
        $dailyMeals = $this->selectedWeek?->dailyMeals ?? collect();
        $this->estimatedPeople = $dailyMeals->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $dailyMeal->estimated_people])->all();
        $this->maximumBudget = $dailyMeals->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $dailyMeal->maximum_budget])->all();
        $this->contributorCount = $dailyMeals->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $dailyMeal->contributor_count])->all();
        $this->plannedMenus = $dailyMeals->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $dailyMeal->menu_id])->all();
        $this->plannedSoups = $dailyMeals->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $dailyMeal->soup_menu_id])->all();
        $this->plannedDesserts = $dailyMeals->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $dailyMeal->dessert_menu_id])->all();
        $this->cookedFlags = $dailyMeals->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => (bool) $dailyMeal->is_cooked])->all();
        $this->actualMenus = $dailyMeals->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $dailyMeal->actual_menu_id])->all();
        $this->actualCosts = $dailyMeals->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->id => $dailyMeal->actual_cost])->all();
        $this->workingDayDates = $this->buildWorkingDayDates();
    }

    /**
     * @return array<string, bool>
     */
    private function buildWorkingDayDates(): array
    {
        $week = $this->selectedWeek;

        if ($week === null) {
            return [];
        }

        $existing = $week->dailyMeals
            ->mapWithKeys(fn (DailyMeal $dailyMeal): array => [$dailyMeal->meal_date->toDateString() => true])
            ->all();

        $dates = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $date = $week->start_date->addDays($offset)->toDateString();
            $dates[$date] = $existing[$date] ?? false;
        }

        return $dates;
    }

    /**
     * @return array<string, Collection<int, Menu>>
     */
    public function getMenuOptionsProperty(): array
    {
        return [
            'main' => Menu::query()->where('type', 'main')->where('is_active', true)->orderBy('name')->get(),
            'soup' => Menu::query()->where('type', 'soup')->where('is_active', true)->orderBy('name')->get(),
            'dessert' => Menu::query()->where('type', 'dessert')->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, Collection<int, Menu>>
     */
    public function getActualMenuOptionsProperty(): array
    {
        return [
            'main' => Menu::query()->where('type', 'main')->where('is_active', true)->orderBy('name')->get(),
            'soup' => Menu::query()->where('type', 'soup')->where('is_active', true)->orderBy('name')->get(),
            'dessert' => Menu::query()->where('type', 'dessert')->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    public function savePlannedMenu(int $mealId, string $type): void
    {
        $meal = $this->calendarMeal($mealId);

        [$property, $column, $typeName] = match ($type) {
            'main' => ['plannedMenus', 'menu_id', 'main'],
            'soup' => ['plannedSoups', 'soup_menu_id', 'soup'],
            'dessert' => ['plannedDesserts', 'dessert_menu_id', 'dessert'],
            default => [null, null, null],
        };

        abort_if($property === null, 422);

        $value = $this->{$property}[$mealId] ?? null;

        $meal->{$column} = $value === '' ? null : $value;

        $warnings = app(MenuSchedulingValidator::class)->warnings($meal);
        $meal->save();

        $this->loadCalendarValues();
        $this->notifyWarnings($warnings, 'Meniul a fost actualizat.');
    }

    public function markCooked(int $mealId): void
    {
        $this->validate([
            "actualCosts.{$mealId}" => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $meal = $this->calendarMeal($mealId);

        $meal->is_cooked = (bool) ($this->cookedFlags[$mealId] ?? false);
        $meal->actual_menu_id = filled($this->actualMenus[$mealId] ?? null) ? $this->actualMenus[$mealId] : null;
        $meal->actual_cost = blank($this->actualCosts[$mealId] ?? null) ? null : $this->actualCosts[$mealId];
        $meal->save();

        $this->loadCalendarValues();

        Notification::make()->title('Starea de gatit, felul real si costul au fost salvate.')->success()->send();
    }

    public function openNewDish(int $mealId, string $type): void
    {
        $this->calendarMeal($mealId);

        $this->newDishMealId = $mealId;
        $this->newDishType = $type;
        $this->newDishName = '';
    }

    public function closeNewDish(): void
    {
        $this->newDishMealId = null;
        $this->newDishType = null;
        $this->newDishName = '';
    }

    public function createDish(): void
    {
        $this->validate([
            'newDishName' => ['required', 'string', 'max:120'],
            'newDishType' => ['required', 'in:main,soup,dessert'],
        ]);

        $menu = Menu::create([
            'name' => $this->newDishName,
            'type' => $this->newDishType,
            'ingredients' => [],
            'is_active' => true,
        ]);

        $mealId = (int) $this->newDishMealId;

        match ($this->newDishType) {
            'main' => $this->plannedMenus[$mealId] = $menu->id,
            'soup' => $this->plannedSoups[$mealId] = $menu->id,
            'dessert' => $this->plannedDesserts[$mealId] = $menu->id,
        };

        $this->savePlannedMenu($mealId, $this->newDishType);

        $this->closeNewDish();

        Notification::make()->title('Felul nou a fost creat si selectat.')->success()->send();
    }

    public function toggleWorkingDay(string $date): void
    {
        $week = $this->selectedWeek;
        abort_if($week === null, 404);

        $existing = $week->dailyMeals->firstWhere(fn (DailyMeal $meal): bool => $meal->meal_date->toDateString() === $date);

        if ($existing !== null) {
            $this->pendingWorkingDayMealId = $existing->id;
            $this->pendingWorkingDayDate = $date;

            return;
        }

        $this->createWorkingDay($date);
    }

    public function createWorkingDay(string $date): void
    {
        $week = $this->selectedWeek;
        abort_if($week === null, 404);

        Gate::authorize('create', DailyMeal::class);
        abort_unless(auth()->user()?->isAdmin(), 403);

        $alreadyExists = $week->dailyMeals->contains(fn (DailyMeal $meal): bool => $meal->meal_date->toDateString() === $date);

        if ($alreadyExists) {
            Notification::make()->title('Ziua exista deja.')->warning()->send();

            return;
        }

        DailyMeal::create([
            'meal_date' => $date,
            'week_id' => $week->id,
            'congregation_id' => $week->congregation_id,
            'estimated_people' => 0,
            'status' => 'draft',
        ]);

        $this->closeSchedulingAction();
        $this->loadCalendarValues();

        Notification::make()->title('Ziua a fost adaugata in program.')->success()->send();
    }

    public function confirmDeleteWorkingDay(): void
    {
        $meal = DailyMeal::query()
            ->whereKey((int) $this->pendingWorkingDayMealId)
            ->where('week_id', $this->weekId)
            ->firstOrFail();

        Gate::authorize('delete', $meal);
        abort_unless(auth()->user()?->isAdmin(), 403);

        DB::transaction(function () use ($meal): void {
            $meal->supplyPlan?->consumptions()->delete();
            $meal->supplyPlan?->delete();
            $meal->volunteers()->delete();
            $meal->workTeams()->delete();
            $meal->delete();
        });

        $this->closeSchedulingAction();
        $this->loadCalendarValues();

        Notification::make()
            ->title('Ziua a fost scoasa din program. Cheltuielile din fondul alimentar au ramas neschimbate.')
            ->success()
            ->send();
    }

    public function openSuggestWeek(int $weekId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->suggestWeekId = $weekId;
    }

    public function suggestMenus(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $week = Week::query()->findOrFail((int) $this->suggestWeekId);

        $command = new FillMenus;
        $filled = $command->fillWeek($week);

        $this->closeSchedulingAction();
        $this->loadCalendarValues();

        Notification::make()
            ->title($filled > 0
                ? "Sugestia a completat {$filled} zile goale."
                : 'Nu exista zile goale de completat.')
            ->success()
            ->send();
    }

    public function openCreateWeek(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->showCreateWeek = true;
        $this->newWeekNumber = (int) (Week::query()->max('week_number') ?? 0) + 1;
        $this->newWeekStartDate = null;
        $this->newWeekCongregationId = null;
    }

    public function closeCreateWeek(): void
    {
        $this->showCreateWeek = false;
        $this->newWeekNumber = null;
        $this->newWeekStartDate = null;
        $this->newWeekCongregationId = null;
        $this->resetErrorBag();
    }

    public function createWeek(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->validate([
            'newWeekNumber' => ['required', 'integer', 'min:1', 'max:520'],
            'newWeekStartDate' => ['required', 'date'],
            'newWeekCongregationId' => ['required', 'integer', 'exists:congregations,id'],
        ]);

        $week = Week::create([
            'week_number' => (int) $this->newWeekNumber,
            'start_date' => CarbonImmutable::parse($this->newWeekStartDate)->toDateString(),
            'congregation_id' => (int) $this->newWeekCongregationId,
        ]);

        $this->weekId = $week->id;
        $this->closeCreateWeek();
        $this->loadCalendarValues();

        Notification::make()->title('Saptamana a fost creata.')->success()->send();
    }

    public function openDeleteWeek(int $weekId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->pendingDeleteWeekId = $weekId;
    }

    public function confirmDeleteWeek(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $week = Week::query()->findOrFail((int) $this->pendingDeleteWeekId);

        Gate::authorize('delete', $week);

        DB::transaction(function () use ($week): void {
            foreach ($week->dailyMeals as $meal) {
                $meal->supplyPlan?->consumptions()->delete();
                $meal->supplyPlan?->delete();
                $meal->volunteers()->delete();
                $meal->workTeams()->delete();
                $meal->delete();
            }

            $week->delete();
        });

        $this->pendingDeleteWeekId = null;
        $this->weekId = $this->weeks->first()?->id;
        $this->loadCalendarValues();

        Notification::make()
            ->title('Saptamana a fost stearsa impreuna cu zilele ei. Cheltuielile din fondul alimentar au ramas neschimbate.')
            ->success()
            ->send();
    }

    public function closeDeleteWeek(): void
    {
        $this->pendingDeleteWeekId = null;
    }

    /**
     * @return array<int, array<int, \App\Models\Volunteer>>
     */
    public function getVolunteersByMealProperty(): array
    {
        return $this->selectedWeek?->dailyMeals
            ->mapWithKeys(fn (DailyMeal $meal): array => [$meal->id => $meal->volunteers->all()])
            ->all() ?? [];
    }

    /**
     * @return array<int, array<int, \App\Models\WorkTeam>>
     */
    public function getTeamsByMealProperty(): array
    {
        return $this->selectedWeek?->dailyMeals
            ->mapWithKeys(fn (DailyMeal $meal): array => [$meal->id => $meal->workTeams->all()])
            ->all() ?? [];
    }

    /**
     * @return array<int, array{income: float, expense: float, balance: float}>
     */
    public function getFoodFundByDateProperty(): array
    {
        $week = $this->selectedWeek;

        if ($week === null) {
            return [];
        }

        $summary = [];

        FoodFundTransaction::query()
            ->whereBetween('transaction_date', [
                $week->start_date->toDateString(),
                $week->start_date->copy()->addDays(6)->toDateString(),
            ])
            ->get()
            ->groupBy(fn (FoodFundTransaction $transaction): string => $transaction->transaction_date->toDateString())
            ->each(function ($transactions, string $date) use (&$summary): void {
                $income = (float) $transactions->where('type', 'income')->sum('amount');
                $expense = (float) $transactions->where('type', 'expense')->sum('amount');

                $summary[$date] = [
                    'income' => $income,
                    'expense' => $expense,
                    'balance' => $income - $expense,
                ];
            });

        return $summary;
    }

    public function openPeople(int $mealId): void
    {
        $this->guardPeopleManagement();
        $this->calendarMealForRole($mealId);

        $this->peopleMealId = $mealId;
        $this->resetVolunteerForm();
    }

    public function closePeople(): void
    {
        $this->peopleMealId = null;
        $this->resetVolunteerForm();
    }

    public function editVolunteer(int $volunteerId): void
    {
        $this->guardPeopleManagement();

        $volunteer = Volunteer::query()
            ->whereKey($volunteerId)
            ->where('daily_meal_id', $this->peopleMealId)
            ->firstOrFail();

        $this->editingVolunteerId = $volunteer->id;
        $this->volunteerName = (string) $volunteer->name;
        $this->volunteerPhone = (string) ($volunteer->phone ?? '');
        $this->volunteerRole = (string) $volunteer->role;
        $this->volunteerHasAllergies = (bool) $volunteer->has_allergies;
        $this->volunteerAllergyDetails = (string) ($volunteer->allergy_details ?? '');
    }

    public function saveVolunteer(): void
    {
        $this->guardPeopleManagement();

        $this->validate([
            'volunteerName' => ['required', 'string', 'max:120'],
            'volunteerPhone' => ['nullable', 'string', 'max:30'],
            'volunteerRole' => ['required', 'string', 'max:60'],
            'volunteerAllergyDetails' => ['nullable', 'string', 'max:500', 'required_if:volunteerHasAllergies,true'],
        ]);

        $meal = $this->calendarMealForRole((int) $this->peopleMealId);

        $attributes = [
            'name' => $this->volunteerName,
            'phone' => $this->volunteerPhone ?: null,
            'role' => $this->volunteerRole,
            'has_allergies' => $this->volunteerHasAllergies,
            'allergy_details' => $this->volunteerHasAllergies ? $this->volunteerAllergyDetails : null,
        ];

        if ($this->editingVolunteerId !== null) {
            $meal->volunteers()->whereKey($this->editingVolunteerId)->update($attributes);
        } else {
            $meal->volunteers()->create($attributes);
        }

        $this->loadCalendarValues();
        $this->resetVolunteerForm();

        Notification::make()->title('Persoana a fost salvata.')->success()->send();
    }

    public function deleteVolunteer(int $volunteerId): void
    {
        $this->guardPeopleManagement();

        Volunteer::query()
            ->whereKey($volunteerId)
            ->where('daily_meal_id', $this->peopleMealId)
            ->delete();

        if ($this->editingVolunteerId === $volunteerId) {
            $this->resetVolunteerForm();
        }

        $this->loadCalendarValues();

        Notification::make()->title('Persoana a fost stearsa.')->success()->send();
    }

    public function cancelVolunteerEdit(): void
    {
        $this->resetVolunteerForm();
    }

    public function notifyMessageCopied(): void
    {
        Notification::make()->title('Mesajul a fost copiat in clipboard.')->success()->send();
    }

    private function resetVolunteerForm(): void
    {
        $this->editingVolunteerId = null;
        $this->volunteerName = '';
        $this->volunteerPhone = '';
        $this->volunteerRole = '';
        $this->volunteerHasAllergies = false;
        $this->volunteerAllergyDetails = '';
        $this->resetErrorBag();
    }

    public function openTeams(int $mealId): void
    {
        $this->calendarMeal($mealId);

        $this->teamsMealId = $mealId;
        $this->resetTeamForm();
    }

    public function closeTeams(): void
    {
        $this->teamsMealId = null;
        $this->resetTeamForm();
    }

    public function editTeam(int $teamId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $team = WorkTeam::query()
            ->whereKey($teamId)
            ->where('daily_meal_id', $this->teamsMealId)
            ->firstOrFail();

        $this->editingTeamId = $team->id;
        $this->teamName = (string) $team->name;
        $this->teamMemberCount = $team->member_count;
        $this->teamStartsAt = $team->starts_at ? substr((string) $team->starts_at, 0, 5) : '';
        $this->teamEndsAt = $team->ends_at ? substr((string) $team->ends_at, 0, 5) : '';
        $this->teamLocation = (string) ($team->location ?? '');
        $this->teamSupplyResponsible = (string) ($team->supply_responsible ?? '');
    }

    public function saveTeam(): void
    {
        $this->validate([
            'teamName' => ['required', 'string', 'max:120'],
            'teamMemberCount' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'teamStartsAt' => ['nullable', 'date_format:H:i'],
            'teamEndsAt' => ['nullable', 'date_format:H:i'],
            'teamLocation' => ['nullable', 'string', 'max:120'],
            'teamSupplyResponsible' => ['nullable', 'string', 'max:120'],
        ]);

        $meal = $this->calendarMeal((int) $this->teamsMealId);

        $attributes = [
            'name' => $this->teamName,
            'member_count' => $this->teamMemberCount,
            'starts_at' => $this->teamStartsAt ?: null,
            'ends_at' => $this->teamEndsAt ?: null,
            'location' => $this->teamLocation ?: null,
            'supply_responsible' => $this->teamSupplyResponsible ?: null,
        ];

        if ($this->editingTeamId !== null) {
            $meal->workTeams()->whereKey($this->editingTeamId)->update($attributes);
        } else {
            $meal->workTeams()->create($attributes);
        }

        $this->loadCalendarValues();
        $this->resetTeamForm();

        Notification::make()->title('Echipa a fost salvata.')->success()->send();
    }

    public function deleteTeam(int $teamId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        WorkTeam::query()
            ->whereKey($teamId)
            ->where('daily_meal_id', $this->teamsMealId)
            ->delete();

        if ($this->editingTeamId === $teamId) {
            $this->resetTeamForm();
        }

        $this->loadCalendarValues();

        Notification::make()->title('Echipa a fost stearsa.')->success()->send();
    }

    public function cancelTeamEdit(): void
    {
        $this->resetTeamForm();
    }

    private function resetTeamForm(): void
    {
        $this->editingTeamId = null;
        $this->teamName = '';
        $this->teamMemberCount = null;
        $this->teamStartsAt = '';
        $this->teamEndsAt = '';
        $this->teamLocation = '';
        $this->teamSupplyResponsible = '';
        $this->resetErrorBag();
    }

    private function guardPeopleManagement(): void
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isCoordinator(), 403);
    }

    private function calendarMealForRole(int $mealId): DailyMeal
    {
        $meal = DailyMeal::query()
            ->whereKey($mealId)
            ->where('week_id', $this->weekId)
            ->firstOrFail();

        $user = auth()->user();

        if ($user?->isCoordinator() && $user->congregation_id !== $meal->congregation_id) {
            abort(403);
        }

        return $meal;
    }

    /**
     * @return array<string, string>
     */
    public function getUncoveredRolesProperty(): array
    {
        $week = $this->selectedWeek;

        if ($week === null) {
            return [];
        }

        $requiredRoles = $week->dailyMeals
            ->flatMap(fn (DailyMeal $meal): array => $meal->workTeams->map(
                fn (WorkTeam $team): string => trim((string) $team->name),
            )->filter()->all())
            ->unique()
            ->values();

        $roles = [];

        foreach ($week->dailyMeals as $meal) {
            $filled = $meal->volunteers
                ->map(fn (Volunteer $volunteer): string => trim((string) $volunteer->role))
                ->filter()
                ->all();

            $missing = $requiredRoles
                ->filter(fn (string $role): bool => ! in_array($role, $filled, true))
                ->values();

            if ($missing->isNotEmpty()) {
                $roles[$meal->meal_date->toDateString()] = $missing->implode(', ');
            }
        }

        return $roles;
    }

    public function getWhatsappMessagesProperty(): array
    {
        $meals = $this->selectedWeek?->dailyMeals;
        $week = $this->selectedWeek;

        if ($meals === null) {
            return ['week' => '', 'days' => []];
        }

        $builder = app(\App\Services\DailyMessageBuilder::class);

        $days = [];

        foreach ($meals as $meal) {
            $days[$meal->id] = $builder->dayMessage($meal);
        }

        $rangeLabel = $week->start_date->translatedFormat('j M').' - '.$week->start_date->copy()->addDays(4)->translatedFormat('j M');

        return [
            'week' => $builder->weekMessage($meals, $week->week_number, $rangeLabel),
            'days' => $days,
        ];
    }

    /**
     * @return Collection<int, \App\Models\Congregation>
     */
    public function getCongregationOptionsProperty(): Collection
    {
        return \App\Models\Congregation::query()->orderBy('name')->get();
    }

    /**
     * @param  Collection<int, SupplyContribution>  $contributions
     * @return list<array{icon: string, label: string}>
     */
    private function buildSupplyIndicators(Collection $contributions): array    {
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
