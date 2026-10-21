<?php

namespace App\Filament\Widgets;

use App\Models\DailyMeal;
use App\Services\MealRequirementCalculator;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class TomorrowMealOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $tomorrow = today()->addDay();
        $meal = DailyMeal::query()
            ->whereDate('meal_date', $tomorrow)
            ->with(['menu', 'soupMenu', 'dessertMenu', 'congregation', 'workTeams', 'volunteers'])
            ->first();

        if ($meal === null) {
            return [
                Stat::make('Maine', 'Fara masa programata')
                    ->description($tomorrow->copy()->locale('ro')->translatedFormat('l, j F Y'))
                    ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                    ->color('gray')
                    ->icon(Heroicon::OutlinedCalendarDays),
            ];
        }

        $allergens = collect([$meal->menu, $meal->soupMenu, $meal->dessertMenu])
            ->filter()
            ->flatMap(fn ($menu) => is_array($menu->allergens) ? $menu->allergens : ['De confirmat'])
            ->unique()
            ->values()
            ->all();

        $recipes = collect([$meal->menu?->name, $meal->soupMenu?->name, $meal->dessertMenu?->name])
            ->filter()
            ->implode(' | ');

        $shoppingPath = URL::route('daily-meal-preparation-sheets.show', ['dailyMeal' => $meal->id]);

        $shoppingList = app(MealRequirementCalculator::class)->calculate($meal)['ingredients'];
        $shoppingSummary = collect($shoppingList)
            ->take(4)
            ->map(fn (array $ingredient): string => $ingredient['name'].' '.rtrim(rtrim(number_format($ingredient['quantity'], 3, ',', '.'), '0'), ',').' '.$ingredient['unit'])
            ->implode(', ');

        if (count($shoppingList) > 4) {
            $shoppingSummary .= ' +'.(count($shoppingList) - 4).' altele';
        }

        $teams = $meal->workTeams
            ->map(fn ($team): string => $team->name
                .($team->starts_at && $team->ends_at ? ' ('.substr((string) $team->starts_at, 0, 5).'-'.substr((string) $team->ends_at, 0, 5).')' : ''))
            ->implode(', ');

        $assignedRoles = $meal->volunteers
            ->map(fn ($volunteer): string => trim((string) $volunteer->role))
            ->filter()
            ->unique()
            ->all();

        $uncoveredRoles = $meal->workTeams
            ->map(fn ($team): string => trim((string) $team->name))
            ->filter()
            ->unique()
            ->reject(fn (string $role): bool => in_array($role, $assignedRoles, true))
            ->values();

        return [
            Stat::make('Maine', $recipes !== '' ? $recipes : 'Meniu neales')
                ->description($tomorrow->copy()->locale('ro')->translatedFormat('l, j F Y').' - '.$meal->estimated_people.' persoane - '.($meal->congregation?->name ?? 'fara congregatie'))
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->color('primary')
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make('Echipe si roluri', $teams !== '' ? $teams : 'Fara echipe programate')
                ->description($uncoveredRoles->isEmpty()
                    ? 'Toate rolurile au o persoana alocata'
                    : 'Neacoperite: '.$uncoveredRoles->implode(', '))
                ->descriptionIcon(Heroicon::OutlinedWrenchScrewdriver)
                ->color($uncoveredRoles->isEmpty() ? 'success' : 'warning')
                ->icon(Heroicon::OutlinedWrenchScrewdriver),
            Stat::make('Alergeni de verificat', $allergens === [] ? 'Niciunul' : implode(', ', $allergens))
                ->description(in_array('De confirmat', $allergens, true) ? 'Reteta nu are lista de alergeni confirmata' : 'Verifica lista completa in fisa de pregatire')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color(in_array('De confirmat', $allergens, true) ? 'warning' : 'success')
                ->icon(Heroicon::OutlinedExclamationTriangle),
            Stat::make('Necesar de cumparat', $shoppingSummary !== '' ? $shoppingSummary : 'Fara ingrediente definite')
                ->description('Estimare pentru '.$meal->estimated_people.' persoane; lista completa in fisa de pregatire')
                ->descriptionIcon(Heroicon::OutlinedShoppingCart)
                ->color('info')
                ->icon(Heroicon::OutlinedDocumentText)
                ->url($shoppingPath),
        ];
    }

    public static function canView(): bool
    {
        return Gate::allows('viewAny', DailyMeal::class);
    }
}
