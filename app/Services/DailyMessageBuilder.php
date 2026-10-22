<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Volunteer;
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

    /**
     * Etichetele rolurilor pentru documentele tiparite (raport PDF).
     *
     * @var array<string, string>
     */
    public const ROLE_LABELS = [
        'Aprovizionare si transport' => 'Aprovizionare și transport',
        'Gatit' => 'Gătit',
        'Impachetare si servire' => 'Împachetare și servire',
        'Donatii' => 'Donații',
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

        foreach ($this->rolesFor($meal) as $role => $people) {
            $lines[] = $role.': '.collect($people)
                ->map(fn (array $person): string => $person['name'].($person['phone'] !== null ? ' ('.$person['phone'].')' : ''))
                ->implode(', ');
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

    /**
     * Persoanele pe roluri pentru o zi, grupate pe rolurile definite.
     * Rolurile fara persoane nu apar in rezultat.
     *
     * @return array<string, list<array{name: string, phone: string|null}>>
     */
    public function rolesFor(DailyMeal $meal): array
    {
        $meal->loadMissing('volunteers');

        $roles = [];

        foreach (self::ROLES as $role) {
            $people = $meal->volunteers
                ->filter(fn (Volunteer $volunteer): bool => $this->volunteerHasRole($volunteer, $role))
                ->map(fn (Volunteer $volunteer): array => [
                    'name' => (string) $volunteer->name,
                    'phone' => filled($volunteer->phone) ? (string) $volunteer->phone : null,
                ])
                ->values()
                ->all();

            if ($people !== []) {
                $roles[$role] = $people;
            }
        }

        return $roles;
    }

    public function uncoveredRoles(DailyMeal $meal): string
    {
        return implode(', ', $this->uncoveredRoleList($meal));
    }

    /**
     * Rolurile obligatorii neacoperite de nicio persoana.
     *
     * @return list<string>
     */
    public function uncoveredRoleList(DailyMeal $meal): array
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
            ->all();
    }

    /**
     * Eticheta scrisa cu diacritice a unui rol.
     */
    public static function roleLabel(string $role): string
    {
        return self::ROLE_LABELS[$role] ?? $role;
    }

    private function volunteerHasRole(Volunteer $volunteer, string $role): bool
    {
        if (is_array($volunteer->roles) && in_array($role, $volunteer->roles, true)) {
            return true;
        }

        return mb_strtolower((string) $volunteer->role) === mb_strtolower($role);
    }
}
