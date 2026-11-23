<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Donation;
use App\Models\Week;
use App\Support\ContactStatus;
use Illuminate\Support\Collection;

/**
 * Datele pagei „Plan sprijin”: zilele cu gătitorul și contribuitorii,
 * ce a promis/adus fiecare și avertismentele de duplicare.
 *
 * Nu returnează prețuri și nu expune date personale dincolo de contactele
 * congregațiilor, care sunt vizibile doar administratorului.
 */
class SupportPlanService
{
    /**
     * @return array{days: list<array<string, mixed>>, categories: list<array<string, mixed>>, warnings: list<array{type: string, message: string}>, unallocated: list<array<string, mixed>>, allocation_days: list<array{id: int, label: string}>, contributions: int, uncontacted: int}
     */
    public function build(?Week $week): array
    {
        $meals = $week !== null
            ? $week->dailyMeals()
                ->with(['cookingCongregation', 'contributingCongregations'])
                ->orderBy('meal_date')
                ->get()
            : DailyMeal::query()
                ->with(['cookingCongregation', 'contributingCongregations'])
                ->orderBy('meal_date')
                ->get();

        $donations = $this->donationsFor($meals);
        $grouped = $this->groupDonations($meals, $donations);
        $days = $meals->map(fn (DailyMeal $meal): array => $this->day($meal, $grouped))->values()->all();

        return [
            'days' => $days,
            'categories' => $this->categories($donations),
            'warnings' => $this->warnings($week, $meals, $donations, $days),
            'unallocated' => $this->unallocated($meals),
            'allocation_days' => $this->allocationDays($meals),
            'contributions' => $donations->count(),
            'uncontacted' => (int) collect($days)->sum('uncontacted_count'),
        ];
    }

    /** @param  Collection<int, DailyMeal>  $meals */
    private function donationsFor(Collection $meals): Collection
    {
        if ($meals->isEmpty()) {
            return collect();
        }

        $mealIds = $meals->pluck('id')->all();
        $dates = $meals->pluck('meal_date')->filter()->map(fn ($date): string => $date->toDateString())->unique()->values()->all();
        $congregationIds = $meals->flatMap->contributingCongregations->pluck('id')->unique()->values()->all();

        return Donation::query()
            ->with(['donorCongregation', 'congregation'])
            ->where('status', '!=', Donation::STATUS_CANCELLED)
            ->where(function ($query) use ($mealIds, $dates, $congregationIds): void {
                $query->whereIn('daily_meal_id', $mealIds);

                if ($dates !== [] && $congregationIds !== []) {
                    $query->orWhere(function ($query) use ($dates, $congregationIds): void {
                        $query->whereIn('donor_congregation_id', $congregationIds)
                            ->where(function ($query) use ($dates): void {
                                $query->whereIn('date', $dates)
                                    ->orWhereIn('donation_date', $dates);
                            });
                    });
                }
            })
            ->orderBy('date')
            ->get();
    }

    /**
     * @param  Collection<int, DailyMeal>  $meals
     * @param  Collection<int, Donation>  $donations
     * @return array<int, array<int, Collection<int, Donation>>>
     */
    private function groupDonations(Collection $meals, Collection $donations): array
    {
        $dateToMeal = $meals
            ->filter(fn (DailyMeal $meal): bool => $meal->meal_date !== null)
            ->groupBy(fn (DailyMeal $meal): string => $meal->meal_date->toDateString())
            ->map(fn (Collection $group): int => (int) $group->first()->id);

        $grouped = [];

        foreach ($donations as $donation) {
            $mealId = $donation->daily_meal_id !== null
                ? (int) $donation->daily_meal_id
                : (int) ($dateToMeal->get($donation->date?->toDateString() ?? '') ?? 0);

            if ($mealId === 0) {
                continue;
            }

            $congregationId = (int) ($donation->donor_congregation_id ?? $donation->congregation_id ?? 0);
            $grouped[$mealId][$congregationId] ??= collect();
            $grouped[$mealId][$congregationId]->push($donation);
        }

        return $grouped;
    }

    /**
     * @param  array<int, array<int, Collection<int, Donation>>>  $grouped
     * @return array<string, mixed>
     */
    private function day(DailyMeal $meal, array $grouped): array
    {
        $contributors = [];

        foreach ($meal->contributingCongregations as $congregation) {
            $pivot = $congregation->pivot;
            $status = (string) ($pivot?->contact_status ?? 'necontactat');

            $contributors[] = [
                'congregation' => $congregation,
                'contact_status' => $status,
                'contact_status_label' => ContactStatus::label($status),
                'contacted_at' => $pivot?->contacted_at,
                'contact_notes' => $pivot?->contact_notes,
                'donations' => $grouped[$meal->id][$congregation->id] ?? collect(),
            ];
        }

        return [
            'meal' => $meal,
            'date_label' => $meal->meal_date?->translatedFormat('D d.m.Y') ?? '-',
            'cooking' => $meal->cookingCongregation,
            'contributors' => $contributors,
            'uncontacted_count' => collect($contributors)->filter(
                fn (array $contributor): bool => $contributor['contact_status'] === 'necontactat',
            )->count(),
        ];
    }

    /** @param  Collection<int, Donation>  $donations */
    private function categories(Collection $donations): array
    {
        $categories = [];

        foreach (Donation::CONTRIBUTION_CATEGORIES as $key => $label) {
            $items = $donations->filter(fn (Donation $donation): bool => $donation->contributionCategory() === $key);

            $entries = $items->map(fn (Donation $donation): array => [
                'donation' => $donation,
                'congregation' => $donation->donorCongregation ?? $donation->congregation,
                'description' => trim((string) $donation->description),
                'quantity' => $donation->quantity !== null ? rtrim(rtrim(number_format((float) $donation->quantity, 2, '.', ''), '0'), '.') : '',
                'unit' => (string) $donation->unit,
                'promised' => $donation->status === Donation::STATUS_PROMISED,
                'date' => $donation->date?->translatedFormat('d.m') ?? '',
            ])->values()->all();

            $categories[] = [
                'key' => $key,
                'label' => $label,
                'entries' => $entries,
                'count' => count($entries),
            ];
        }

        return $categories;
    }

    /**
     * Donatiile care au congregatie/descriere, dar nu au fost inca legate de o zi.
     *
     * @param  Collection<int, DailyMeal>  $meals
     * @return list<array<string, mixed>>
     */
    private function unallocated(Collection $meals): array
    {
        return Donation::query()
            ->with(['donorCongregation', 'congregation', 'ingredient'])
            ->whereNull('daily_meal_id')
            ->where('status', '!=', Donation::STATUS_CANCELLED)
            ->orderBy('date')
            ->get()
            ->map(function (Donation $donation) use ($meals): array {
                $date = $donation->date?->toDateString() ?? $donation->donation_date?->toDateString();
                $suggested = $date === null
                    ? null
                    : $meals->first(fn (DailyMeal $meal): bool => $meal->meal_date?->toDateString() === $date);

                return [
                    'donation' => $donation,
                    'congregation' => $donation->donorCongregation ?? $donation->congregation,
                    'description' => trim((string) $donation->description),
                    'quantity' => $donation->quantity !== null
                        ? rtrim(rtrim(number_format((float) $donation->quantity, 2, '.', ''), '0'), '.')
                        : '',
                    'unit' => (string) $donation->unit,
                    'date' => $donation->date?->translatedFormat('d.m.Y') ?? '',
                    'storage' => $donation->storageLabel(),
                    'delivery_until' => $donation->deliveryUntilLabel(),
                    'suggested_meal_id' => $suggested?->id,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Zilele din filtrul curent, folosite la alocarea unei donatii fara zi.
     *
     * @param  Collection<int, DailyMeal>  $meals
     * @return list<array{id: int, label: string}>
     */
    private function allocationDays(Collection $meals): array
    {
        return $meals
            ->filter(fn (DailyMeal $meal): bool => $meal->meal_date !== null)
            ->map(fn (DailyMeal $meal): array => [
                'id' => (int) $meal->id,
                'label' => sprintf(
                    '%s · gătește %s',
                    $meal->meal_date->translatedFormat('D d.m.Y'),
                    $meal->cookingCongregation?->name ?? '—',
                ),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, DailyMeal>  $meals
     * @param  Collection<int, Donation>  $donations
     * @param  list<array<string, mixed>>  $days
     * @return list<array{type: string, message: string}>
     */
    private function warnings(?Week $week, Collection $meals, Collection $donations, array $days): array
    {
        $warnings = [];
        $labels = Donation::CONTRIBUTION_CATEGORIES;

        $described = $donations->filter(fn (Donation $donation): bool => trim((string) $donation->description) !== '');

        $groups = $described->groupBy(function (Donation $donation): string {
            $category = $donation->contributionCategory() ?? 'altele';

            return $category.'|'.$this->stem($this->normalizeProduct((string) $donation->description));
        });

        foreach ($groups as $group) {
            $congregationCount = $group->pluck('donor_congregation_id')->filter()->unique()->count();

            if ($congregationCount < 2) {
                continue;
            }

            $product = $this->normalizeProduct((string) $group->first()->description);
            $perDay = $group->groupBy(fn (Donation $donation): int => (int) $donation->daily_meal_id);
            $maxPerDay = $perDay->map(
                fn (Collection $dayGroup): int => $dayGroup->pluck('donor_congregation_id')->filter()->unique()->count(),
            )->max() ?? 0;

            if ($maxPerDay >= 3) {
                $date = $meals->firstWhere('id', $perDay->search(
                    fn (Collection $dayGroup): bool => $dayGroup->pluck('donor_congregation_id')->filter()->unique()->count() >= 3,
                ))?->meal_date?->translatedFormat('d.m');

                $warnings[] = [
                    'type' => 'duplicate',
                    'message' => sprintf('%d congregații aduc %s în aceeași zi%s.', $maxPerDay, $product, $date ? ' ('.$date.')' : ''),
                ];
            } elseif ($congregationCount >= 3) {
                $warnings[] = [
                    'type' => 'duplicate',
                    'message' => sprintf('%d congregații aduc %s în această săptămână.', $congregationCount, $product),
                ];
            } else {
                $warnings[] = [
                    'type' => 'duplicate',
                    'message' => sprintf('%d congregații aduc %s.', $congregationCount, $product),
                ];
            }
        }

        $withoutDescription = $donations->filter(fn (Donation $donation): bool => trim((string) $donation->description) === '');

        foreach ($meals as $meal) {
            foreach (array_keys($labels) as $category) {
                $count = $withoutDescription
                    ->filter(fn (Donation $donation): bool => (int) $donation->daily_meal_id === (int) $meal->id
                        && $donation->contributionCategory() === $category)
                    ->pluck('donor_congregation_id')
                    ->filter()
                    ->unique()
                    ->count();

                if ($count >= 3) {
                    $warnings[] = [
                        'type' => 'duplicate',
                        'message' => sprintf('%d congregații aduc %s fără a preciza produsul în ziua de %s.', $count, mb_strtolower($labels[$category]), $meal->meal_date?->translatedFormat('d.m') ?? '-'),
                    ];
                }
            }
        }

        if ($week !== null) {
            foreach ($labels as $key => $label) {
                if (! $donations->contains(fn (Donation $donation): bool => $donation->contributionCategory() === $key)) {
                    $warnings[] = [
                        'type' => 'missing_category',
                        'message' => sprintf('Nimeni nu aduce %s în această săptămână.', mb_strtolower($label)),
                    ];
                }
            }
        }

        foreach ($days as $day) {
            if ($day['uncontacted_count'] === 0) {
                continue;
            }

            $names = collect($day['contributors'])
                ->filter(fn (array $contributor): bool => $contributor['contact_status'] === 'necontactat')
                ->map(fn (array $contributor): string => (string) $contributor['congregation']->name)
                ->implode(', ');

            $warnings[] = [
                'type' => 'uncontacted',
                'message' => sprintf('Ziua de %s are %d congregații necontactate: %s.', $day['date_label'], $day['uncontacted_count'], $names),
            ];
        }

        return $warnings;
    }

    private function normalizeProduct(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = preg_replace('/^\d+(?:[.,]\d+)?\s*(?:kg|g|grame|bucăți|bucati|pachete|pachet|cutii|cutie|sticle|sticla|l)\s+(?:de\s+)?/u', '', $value) ?? $value;
        $value = preg_replace('/^(?:de|cu|niște|un|o)\s+/u', '', $value) ?? $value;

        return trim($value, " \t\n\r\0\x0B,;.:-()");
    }

    private function stem(string $value): string
    {
        $clean = preg_replace('/[^a-zăâîșț0-9 ]/u', '', $value) ?? $value;
        $word = explode(' ', $clean)[0] ?? '';

        return mb_substr($word, 0, 5);
    }
}
