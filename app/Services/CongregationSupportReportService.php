<?php

namespace App\Services;

use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Donation;
use App\Models\SupplyContribution;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * Agregarea sprijinului oferit de fiecare congregatie: zile de contributie,
 * zile in care gateste, donatii si consumabile. Nu include date personale.
 */
class CongregationSupportReportService
{
    /**
     * @return Collection<int, array{congregation: Congregation, type: string, contribution_days: int, cooking_days: int, donations_count: int, donated_amount: float, food_donations_count: int, supply_count: int, support_notes: ?string}>
     */
    public function rows(?string $from = null, ?string $to = null): Collection
    {
        return Congregation::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Congregation $congregation): array => [
                'congregation' => $congregation,
                ...$this->summary($congregation, $from, $to),
            ]);
    }

    /**
     * @return array{type: string, contribution_days: int, cooking_days: int, donations_count: int, donated_amount: float, food_donations_count: int, supply_count: int, support_notes: ?string}
     */
    public function summary(Congregation $congregation, ?string $from = null, ?string $to = null): array
    {
        $contributionDays = $congregation->contributingDailyMeals();
        $cookingDays = DailyMeal::query()->where('congregation_id', $congregation->id);
        $donations = $this->donationsFor($congregation);
        $supplies = SupplyContribution::query()->where('congregation_id', $congregation->id);

        $this->applyDateFilter($contributionDays, 'meal_date', $from, $to);
        $this->applyDateFilter($cookingDays, 'meal_date', $from, $to);
        $this->applyDateFilter($donations, 'date', $from, $to);
        $this->applyDateFilter($supplies, 'delivery_date', $from, $to);

        $foodDonations = (clone $donations)
            ->where('kind', Donation::KIND_FOOD)
            ->whereIn('status', [Donation::STATUS_RECEIVED, Donation::STATUS_USED]);

        return [
            'type' => (string) $congregation->type,
            'contribution_days' => (int) $contributionDays->count(),
            'cooking_days' => (int) $cookingDays->count(),
            'donations_count' => (int) $donations->count(),
            'donated_amount' => round((float) $donations->sum('amount_ron'), 2),
            'food_donations_count' => (int) $foodDonations->count(),
            'supply_count' => (int) $supplies->count(),
            'support_notes' => $congregation->support_notes,
        ];
    }

    /**
     * @param  Collection<int, array{congregation: Congregation, type: string, contribution_days: int, cooking_days: int, donations_count: int, donated_amount: float, food_donations_count: int, supply_count: int, support_notes: ?string}>  $rows
     * @return array{contribution_days: int, cooking_days: int, donations_count: int, donated_amount: float, food_donations_count: int, supply_count: int}
     */
    public function totals(Collection $rows): array
    {
        return [
            'contribution_days' => (int) $rows->sum('contribution_days'),
            'cooking_days' => (int) $rows->sum('cooking_days'),
            'donations_count' => (int) $rows->sum('donations_count'),
            'donated_amount' => round((float) $rows->sum('donated_amount'), 2),
            'food_donations_count' => (int) $rows->sum('food_donations_count'),
            'supply_count' => (int) $rows->sum('supply_count'),
        ];
    }

    public function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function donationsFor(Congregation $congregation): Builder
    {
        return Donation::query()
            ->where(function (Builder $query) use ($congregation): void {
                $query->where('donor_congregation_id', $congregation->id)
                    ->orWhere('congregation_id', $congregation->id);
            });
    }

    /**
     * @param  Builder<Model>|Relation<Model>  $query
     */
    private function applyDateFilter(mixed $query, string $column, ?string $from, ?string $to): void
    {
        if ($from !== null) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate($column, '<=', $to);
        }
    }
}
