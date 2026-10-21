<?php

namespace App\Services;

use App\Models\DailyMeal;
use Illuminate\Support\Collection;

class DailyMessageBuilder
{
    /**
     * Toate rolurile posibile.
     *
     * @var array<int, string>
     */
    public const ROLES = [
        'Aprovizionare si transport',
        'Gatit',
        'Impachetare si servire',
        'Donatii',
    ];

    /**
     * Rolurile care intra in calculul "roluri neacoperite" (Donatii este optional).
     *
     * @var array<int, string>
     */
    public const REQUIRED_ROLES = [
        'Aprovizionare si transport',
        'Gatit',
        'Impachetare si servire',
    ];

    public function dayMessage(DailyMeal $meal): string
    {
        $meal->loadMissing(['menu', 'soupMenu', 'dessertMenu', 'congregation', 'workTeams', 'volunteers']);

        $lines = [
            $meal->meal_date->translatedFormat('D, d M Y').' – '.($meal->congregation?->name ?? 'nealocata').' – '.($meal->menu?->name ?? '— fara meniu —').'… – '.($meal->soupMenu?->name ?? '-').' – '.($meal->dessertMenu?->name ?? '-').' – '.$meal->portions().' portii',
        ];

        $teams = $meal->workTeams
            ->map(fn ($team): string => $team->name
                .($team->starts_at && $team->ends_at ? ' ('.substr((string) $team->starts_at, 0, 5).'-'.substr((string) $team->ends_at, 0, 5).')' : ''))
            ->implode(', ');

        if ($teams !== '') {
            $lines[] = 'Echipa: '.$teams;
        }

        foreach (self::ROLES as $role) {
            $people = $meal->volunteers
                ->filter(function ($volunteer) use ($role): bool {
                    if (is_array($volunteer->roles) && in_array($role, $volunteer->roles, true)) {
                        return true;
                    }

                    return mb_strtolower((string) $volunteer->role) === mb_strtolower($role);
                })
                ->map(fn ($volunteer): string => $volunteer->name.($volunteer->phone ? ' ('.$volunteer->phone.')' : ''))
                ->implode(', ');

            if ($people !== '') {
                $lines[] = $role.': '.$people;
            }
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
        $builder = $this;

        $lines = ['Planul saptamanii '.$weekNumber.' ('.$rangeLabel.')'];

        foreach ($meals as $meal) {
            $meal->loadMissing(['menu', 'soupMenu', 'dessertMenu', 'congregation', 'workTeams', 'volunteers']);
            $lines[] = '';
            $lines[] = $builder->dayMessage($meal);
        }

        return ltrim(implode("\n", $lines));
    }

    public function uncoveredRoles(DailyMeal $meal): string
    {
        $meal->loadMissing('volunteers');

        $assignedRoles = $meal->volunteers
            ->flatMap(fn ($volunteer): array => is_array($volunteer->roles) ? $volunteer->roles : [])
            ->map(fn ($role): string => mb_strtolower(trim((string) $role)))
            ->filter()
            ->all();

        if ($assignedRoles === []) {
            $assignedRoles = $meal->volunteers
                ->map(fn ($volunteer): string => mb_strtolower(trim((string) $volunteer->role)))
                ->filter()
                ->all();
        }

        return collect(self::REQUIRED_ROLES)
            ->reject(fn (string $role): bool => in_array(mb_strtolower($role), $assignedRoles, true))
            ->values()
            ->implode(', ');
    }
}
