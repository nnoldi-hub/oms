<?php

namespace App\Filament\Pages;

use App\Models\DailyMeal;
use App\Models\Week;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ProgramGeneral extends Page
{
    protected string $view = 'filament.pages.program-general';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Program general';

    protected static string|\UnitEnum|null $navigationGroup = 'Planificare';

    protected static ?int $navigationSort = 2;

    public ?int $weekId = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() ?? false) || ($user?->isCoordinator() ?? false);
    }

    public function mount(): void
    {
        $this->weekId = $this->weeks->first()?->id;
    }

    /**
     * Query propriu acestei pagini, nescopat pe congregatia coordonatorului:
     * pagina este strict pentru citire, iar resursa DailyMeal isi pastreaza
     * restrictiile existente.
     */
    public function getWeeksProperty(): Collection
    {
        return Week::query()
            ->orderBy('week_number')
            ->with([
                'dailyMeals' => fn (HasMany $query) => $query->orderBy('meal_date')->orderBy('id'),
                'dailyMeals.congregation',
                'dailyMeals.menu',
                'dailyMeals.soupMenu',
                'dailyMeals.dessertMenu',
                'dailyMeals.actualMenu',
            ])
            ->get();
    }

    public function getSelectedWeekProperty(): ?Week
    {
        return $this->weeks->firstWhere('id', $this->weekId);
    }

    /**
     * Randurile tabelului, doar cu date de programare (fara costuri, portii,
     * persoane sau contributii).
     *
     * @return list<array{date: string, congregation: string, main: ?string, soup: ?string, dessert: ?string, is_free_day: bool, is_cooked: bool, actual: ?string}>
     */
    public function getProgramDaysProperty(): array
    {
        return $this->selectedWeek?->dailyMeals
            ->map(fn (DailyMeal $meal): array => [
                'date' => $meal->meal_date?->translatedFormat('l, d.m.Y') ?? '-',
                'congregation' => $meal->congregation?->name ?? 'Nealocata',
                'main' => $meal->menu?->name,
                'soup' => $meal->soupMenu?->name,
                'dessert' => $meal->dessertMenu?->name,
                'is_free_day' => (bool) $meal->is_free_day,
                'is_cooked' => (bool) $meal->is_cooked,
                'actual' => $meal->actualMenu?->name,
            ])
            ->all() ?? [];
    }
}
