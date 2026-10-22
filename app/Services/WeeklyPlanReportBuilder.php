<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Menu;
use App\Models\Week;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class WeeklyPlanReportBuilder
{
    public function __construct(
        private WeeklyDaySheets $daySheets,
        private DailyMessageBuilder $messageBuilder,
    ) {}

    /**
     * Datele raportului "Planul saptamanii" (PDF) pentru o saptamana.
     *
     * Nu include contributiile persoanelor si nicio alta suma financiara
     * individuala, ci doar costurile estimate ale meselor.
     *
     * @return array<string, mixed>
     */
    public function build(Week $week, bool $includePhones = true): array
    {
        $week->loadMissing(['congregation', 'dailyMeals']);

        $days = $this->daySheets->for($week)
            ->map(fn (array $sheet): array => $this->day($sheet, $includePhones))
            ->values()
            ->all();

        $cookingDays = collect($days)->reject(fn (array $day): bool => $day['is_free_day']);
        $totalPortions = (int) $cookingDays->sum('portions');
        $totalCost = round((float) $cookingDays->sum('cost'), 2);
        $hasMissingPrices = $cookingDays->contains(fn (array $day): bool => $day['has_missing_prices']);

        return [
            'title' => 'Masa de prânz pe șantier – Săptămâna '.$week->week_number,
            'range' => $this->range($week),
            'days' => $days,
            'totals' => [
                'days' => $cookingDays->count(),
                'portions' => $totalPortions,
                'cost' => $totalCost,
                'has_missing_prices' => $hasMissingPrices,
                'cost_per_portion' => $totalPortions > 0 && ! $hasMissingPrices
                    ? round($totalCost / $totalPortions, 2)
                    : null,
            ],
            'contacts' => $this->contacts($week, $includePhones),
            'allergens' => $this->allergens($days),
            'generated_at' => now(),
        ];
    }

    /**
     * @param  array<string, mixed>  $sheet
     * @return array<string, mixed>
     */
    private function day(array $sheet, bool $includePhones): array
    {
        $meal = $sheet['meal'];
        $isFreeDay = (bool) $meal->is_free_day;
        $components = collect($sheet['components'])->keyBy('key');
        $portions = $isFreeDay ? 0 : (int) $sheet['portions'];
        $hasMissingPrices = $isFreeDay ? false : (bool) $sheet['cost']['has_missing_prices'];
        $cost = $isFreeDay ? 0.0 : round((float) $sheet['cost']['total_cost'], 2);

        return [
            'meal' => $meal,
            'weekday' => $this->weekdayLabel($meal->meal_date),
            'date' => $meal->meal_date->format('d.m.Y'),
            'short_label' => $this->shortLabel($meal->meal_date),
            'congregation' => $meal->congregation?->name,
            'is_free_day' => $isFreeDay,
            'main' => $this->menuName($components->get('main')),
            'soup' => $this->menuName($components->get('soup')),
            'dessert' => $this->menuName($components->get('dessert')),
            'portions' => $portions,
            'portions_are_estimated' => $meal->confirmed_people === null,
            'cost' => $cost,
            'has_missing_prices' => $hasMissingPrices,
            'cost_per_portion' => $portions > 0 && ! $hasMissingPrices ? round($cost / $portions, 2) : null,
            'roles' => $this->roles($meal, $includePhones),
            'uncovered_roles' => $isFreeDay ? [] : $this->uncoveredRoles($meal),
            'allergens' => $this->dayAllergens($components),
            'allergens_status' => $this->dayAllergensStatus($components),
        ];
    }

    /**
     * @return list<array{label: string, people: list<array{name: string, phone: string|null}>}>
     */
    private function roles(DailyMeal $meal, bool $includePhones): array
    {
        return collect($this->messageBuilder->rolesFor($meal))
            ->map(fn (array $people, string $role): array => [
                'label' => DailyMessageBuilder::roleLabel($role),
                'people' => collect($people)
                    ->map(fn (array $person): array => [
                        'name' => $person['name'],
                        'phone' => $includePhones ? $person['phone'] : null,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function uncoveredRoles(DailyMeal $meal): array
    {
        return collect($this->messageBuilder->uncoveredRoleList($meal))
            ->map(fn (string $role): string => DailyMessageBuilder::roleLabel($role))
            ->all();
    }

    /**
     * Persoanele de contact (asistentul responsabil) ale congregatiilor din saptamana.
     *
     * @return list<array{congregation: string, name: string, phone: string|null, email: string|null}>
     */
    private function contacts(Week $week, bool $includePhones): array
    {
        $congregations = collect();

        if ($week->congregation !== null) {
            $congregations->push($week->congregation);
        }

        foreach ($week->dailyMeals as $meal) {
            if ($meal->congregation !== null) {
                $congregations->push($meal->congregation);
            }
        }

        return $congregations
            ->unique('id')
            ->filter(fn (Congregation $congregation): bool => filled($congregation->assistant_name))
            ->map(fn (Congregation $congregation): array => [
                'congregation' => (string) $congregation->name,
                'name' => (string) $congregation->assistant_name,
                'phone' => $includePhones && filled($congregation->assistant_phone)
                    ? (string) $congregation->assistant_phone
                    : null,
                'email' => filled($congregation->assistant_email) ? (string) $congregation->assistant_email : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Alergenii declarati pe zile.
     *
     * Toate zilele apar in lista: zilele cu alergeni declarati afiseaza lista,
     * zilele in care toate meniurile sunt marcate explicit "fara alergeni" afiseaza
     * "fara alergeni (declarat)", iar celelalte sunt marcate "nedeclarat - de verificat".
     *
     * @param  array<int, array<string, mixed>>  $days
     * @return list<array{label: string, allergens: list<string>, text: string}>
     */
    private function allergens(array $days): array
    {
        return collect($days)
            ->map(fn (array $day): array => [
                'label' => $day['short_label'],
                'allergens' => $day['allergens'],
                'text' => $this->allergensText($day['allergens_status'] ?? 'nedeclarat', $day['allergens']),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $allergens
     */
    private function allergensText(string $status, array $allergens): string
    {
        if ($status === 'fara_alergeni') {
            return 'fara alergeni (declarat)';
        }

        if ($status === 'declarate') {
            return implode(', ', $allergens);
        }

        return 'nedeclarat – de verificat';
    }

    /**
     * Starea allergenilor pentru o zi: "declarate", "fara_alergeni" sau "nedeclarat".
     *
     * @param  Collection<string, array<string, mixed>>  $components
     */
    private function dayAllergensStatus($components): string
    {
        $statuses = $components
            ->map(fn (array $component): string => $this->menuAllergensStatus($component['menu'] ?? null))
            ->filter()
            ->unique()
            ->values();

        if ($statuses->isEmpty() || $statuses->contains('nedeclarat')) {
            return 'nedeclarat';
        }

        return $statuses->every(fn (string $status): bool => $status === 'fara_alergeni')
            ? 'fara_alergeni'
            : 'declarate';
    }

    private function menuAllergensStatus(?Menu $menu): string
    {
        if (! $menu instanceof Menu) {
            return '';
        }

        $allergens = is_array($menu->allergens) ? $menu->allergens : [];

        if ($allergens !== []) {
            return 'declarate';
        }

        return $menu->declaresNoAllergens() ? 'fara_alergeni' : 'nedeclarat';
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $components
     * @return list<string>
     */
    private function dayAllergens($components): array
    {
        return $components
            ->flatMap(fn (array $component): array => $this->menuAllergens($component['menu'] ?? null))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $component
     */
    private function menuName(?array $component): ?string
    {
        $menu = $component['menu'] ?? null;

        return $menu instanceof Menu ? (string) $menu->name : null;
    }

    /**
     * @return list<string>
     */
    private function menuAllergens(?Menu $menu): array
    {
        if (! $menu instanceof Menu || ! is_array($menu->allergens)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($allergen): string => trim((string) $allergen),
            $menu->allergens,
        )));
    }

    /**
     * Perioada raportului: de la prima la ultima masa programata, nu saptamana calendaristica.
     */
    private function range(Week $week): string
    {
        $meals = $week->dailyMeals->sortBy(fn (DailyMeal $meal): int => $meal->meal_date->getTimestamp());
        $start = $meals->first()?->meal_date ?? $week->start_date;
        $end = $meals->last()?->meal_date ?? $start;

        return $start->translatedFormat('j F Y').' – '.$end->translatedFormat('j F Y');
    }

    private function weekdayLabel(CarbonInterface $date): string
    {
        return mb_convert_case($date->translatedFormat('l'), MB_CASE_TITLE, 'UTF-8');
    }

    private function shortLabel(CarbonInterface $date): string
    {
        return mb_convert_case($date->translatedFormat('D'), MB_CASE_TITLE, 'UTF-8').', '.$date->translatedFormat('j M');
    }
}
