<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Menu;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class MenuUsageInsight
{
    /** "Recent" = ultima folosire cu mai putin de RECENT_DAYS zile in urma. */
    public const RECENT_DAYS = 14;

    /** "Des gatit" = cel putin OFTEN_COOKED_COUNT folosiri in fereastra de USAGE_WINDOW_WEEKS saptamani. */
    public const OFTEN_COOKED_COUNT = 3;

    public const USAGE_WINDOW_WEEKS = 6;

    /** @var array<int, Collection<int, array{id: int, date: string, congregation: string}>> */
    private array $usagesCache = [];

    /**
     * Datele de folosire ale unui meniu raportate la o data de referinta,
     * pentru toate congregatiile: ultima folosire din trecut, numarul de
     * folosiri din fereastra recenta si urmatoarea programare viitoare.
     *
     * @param  Menu|int  $menu
     * @param  \DateTimeInterface|string  $date
     * @param  DailyMeal|null  $excludeDay  ziua curent editata, exclusa din calcule
     * @return array{last_used: ?array{date: string, congregation: string, days_passed: int}, used_in_window: int, next_scheduled: ?array{date: string, congregation: string}}
     */
    public function insight($menu, $date, ?DailyMeal $excludeDay = null): array
    {
        $menuId = $menu instanceof Menu ? (int) $menu->getKey() : (int) $menu;
        $reference = CarbonImmutable::parse($date)->startOfDay();

        $usages = $this->menuUsages($menuId);

        if ($excludeDay !== null) {
            $usages = $usages->reject(fn (array $usage): bool => $usage['id'] === (int) $excludeDay->getKey())->values();
        }

        $past = $usages
            ->filter(fn (array $usage): bool => CarbonImmutable::parse($usage['date'])->isBefore($reference))
            ->sortByDesc('date')
            ->values();

        $lastUsed = $past->first();

        $windowStart = $reference->subWeeks(self::USAGE_WINDOW_WEEKS)->startOfDay();

        $usedInWindow = $past
            ->filter(fn (array $usage): bool => CarbonImmutable::parse($usage['date'])->gte($windowStart))
            ->count();

        $nextScheduled = $usages
            ->filter(fn (array $usage): bool => CarbonImmutable::parse($usage['date'])->isAfter($reference))
            ->sortBy('date')
            ->first();

        return [
            'last_used' => $lastUsed === null ? null : [
                'date' => $lastUsed['date'],
                'congregation' => $lastUsed['congregation'],
                'days_passed' => (int) CarbonImmutable::parse($lastUsed['date'])->diffInDays($reference),
            ],
            'used_in_window' => $usedInWindow,
            'next_scheduled' => $nextScheduled === null ? null : [
                'date' => $nextScheduled['date'],
                'congregation' => $nextScheduled['congregation'],
            ],
        ];
    }

    /**
     * Notele de folosire ale unui meniu intr-o zi (gatit recent, gatit des,
     * programat si in alta zi). Folosite atat pe cartonasele din calendar,
     * cat si in notificarile pentru administrator.
     *
     * @param  Menu|int  $menu
     * @param  \DateTimeInterface|string  $date
     * @return array<int, string>
     */
    public function notes($menu, $date, ?DailyMeal $excludeDay = null): array
    {
        $insight = $this->insight($menu, $date, $excludeDay);
        $notes = [];

        $lastUsed = $insight['last_used'];

        if ($lastUsed !== null && $lastUsed['days_passed'] < self::RECENT_DAYS) {
            $notes[] = sprintf(
                'Gătit ultima dată pe %s (acum %d %s), la %s',
                CarbonImmutable::parse($lastUsed['date'])->translatedFormat('j M'),
                $lastUsed['days_passed'],
                $lastUsed['days_passed'] === 1 ? 'zi' : 'zile',
                $lastUsed['congregation'],
            );
        }

        if ($insight['used_in_window'] >= self::OFTEN_COOKED_COUNT) {
            $notes[] = sprintf(
                'Gătit de %d ori în ultimele %d săptămâni',
                $insight['used_in_window'],
                self::USAGE_WINDOW_WEEKS,
            );
        }

        $nextScheduled = $insight['next_scheduled'];

        if ($nextScheduled !== null) {
            $notes[] = sprintf(
                'Programat și pe %s, la %s',
                CarbonImmutable::parse($nextScheduled['date'])->translatedFormat('j M'),
                $nextScheduled['congregation'],
            );
        }

        return $notes;
    }

    /**
     * Zilele programate sau gatite (toate congregatiile) in care apare meniul:
     * ca fel principal, ciorba, desert sau ca fel real al unei zile gatite.
     * Zilele libere sunt ignorate.
     *
     * @return Collection<int, array{id: int, date: string, congregation: string}>
     */
    private function menuUsages(int $menuId): Collection
    {
        if (isset($this->usagesCache[$menuId])) {
            return $this->usagesCache[$menuId];
        }

        return $this->usagesCache[$menuId] = DailyMeal::query()
            ->where('is_free_day', false)
            ->where(function ($query) use ($menuId) {
                $query
                    ->where('menu_id', $menuId)
                    ->orWhere('soup_menu_id', $menuId)
                    ->orWhere('dessert_menu_id', $menuId)
                    ->orWhere(fn ($cooked) => $cooked->where('is_cooked', true)->where('actual_menu_id', $menuId));
            })
            ->leftJoin('congregations', 'congregations.id', '=', 'daily_meals.congregation_id')
            ->orderByDesc('daily_meals.meal_date')
            ->get(['daily_meals.id', 'daily_meals.meal_date', 'congregations.name as congregation_name'])
            ->map(fn (DailyMeal $meal): array => [
                'id' => (int) $meal->id,
                'date' => $meal->meal_date->toDateString(),
                'congregation' => (string) ($meal->congregation_name ?? 'Nealocata'),
            ]);
    }
}
