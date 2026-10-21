<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\WorkTeam;
use Illuminate\Support\Collection;

class DailyMessageBuilder
{
    public function dayMessage(DailyMeal $meal): string
    {
        $dishes = collect([$meal->menu?->name, $meal->soupMenu?->name, $meal->dessertMenu?->name])
            ->filter()
            ->implode(' | ');

        $lines = [
            'Masa din '.$meal->meal_date->translatedFormat('l, j F Y'),
            'Congregatie: '.($meal->congregation?->name ?? 'nealocata'),
            'Meniu: '.($dishes !== '' ? $dishes : '— fara meniu —'),
            'Portii: '.$meal->portions(),
        ];

        $teams = $meal->workTeams
            ->map(fn (WorkTeam $team): string => $team->name
                .($team->starts_at && $team->ends_at ? ' ('.substr((string) $team->starts_at, 0, 5).'-'.substr((string) $team->ends_at, 0, 5).')' : ''))
            ->implode(', ');

        if ($teams !== '') {
            $lines[] = 'Echipe: '.$teams;
        }

        $uncovered = $this->uncoveredRoles($meal);

        if ($uncovered !== '') {
            $lines[] = 'Roluri neacoperite: '.$uncovered;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  Collection<int, DailyMeal>  $meals
     */
    public function weekMessage(Collection $meals, int $weekNumber, string $rangeLabel): string
    {
        $lines = ['Planul saptamanii '.$weekNumber.' ('.$rangeLabel.')'];

        foreach ($meals as $meal) {
            $dish = $meal->menu?->name ?? '— fara meniu —';
            $lines[] = $meal->meal_date->translatedFormat('D, j M').': '.$dish.' - '.$meal->portions().' portii';
        }

        return implode("\n", $lines);
    }

    public function uncoveredRoles(DailyMeal $meal): string
    {
        $assignedRoles = $meal->volunteers
            ->map(fn ($volunteer): string => trim((string) $volunteer->role))
            ->filter()
            ->all();

        return $meal->workTeams
            ->map(fn (WorkTeam $team): string => trim((string) $team->name))
            ->filter()
            ->unique()
            ->reject(fn (string $role): bool => in_array($role, $assignedRoles, true))
            ->values()
            ->implode(', ');
    }
}
