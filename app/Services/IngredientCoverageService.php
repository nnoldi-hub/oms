<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Donation;
use App\Models\Ingredient;
use App\Support\PracticalQuantity;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Acoperirea ingredientelor unei zile de masa cu donatii alimentare primite/promise.
 * Nu returneaza preturi si nu modifica listele de cumparaturi.
 */
class IngredientCoverageService
{
    public function __construct(private DailyShoppingListCalculator $shoppingList) {}

    /**
     * @return list<array{name: string, unit: string, need: float, covered: float, remaining: float, label: string}>
     */
    public function forMeal(DailyMeal $meal): array
    {
        $donations = $this->donationsFor($meal);

        return collect($this->shoppingList->ingredientNeeds($meal))
            ->map(fn (array $need): array => $this->coverage(
                (string) $need['name'],
                (string) $need['unit'],
                (float) $need['quantity'],
                $donations,
            ))
            ->values()
            ->all();
    }

    /**
     * @return array{name: string, unit: string, need: float, covered: float, remaining: float, label: string}|null
     */
    public function forIngredient(DailyMeal $meal, ?Ingredient $ingredient): ?array
    {
        if ($ingredient === null) {
            return null;
        }

        $need = collect($this->forMeal($meal))->first(
            fn (array $item): bool => $this->key($item['name'], $item['unit']) === $this->key($ingredient->name, $ingredient->unit),
        );

        if ($need !== null) {
            return $need;
        }

        return $this->coverage(
            (string) $ingredient->name,
            (string) $ingredient->unit,
            0.0,
            $this->donationsFor($meal),
        );
    }

    public function overOffer(?array $coverage, float $quantity): ?string
    {
        if ($coverage === null || $quantity <= 0) {
            return null;
        }

        $remaining = (float) $coverage['remaining'];

        if ($quantity <= $remaining + 0.0001) {
            return null;
        }

        $excess = round($quantity - $remaining, 3);

        return sprintf(
            'Atenție: oferta depășește necesarul rămas cu %s. Nu blocăm salvarea.',
            PracticalQuantity::format($excess, (string) $coverage['unit']),
        );
    }

    /** @param  Collection<int, Donation>  $donations */
    private function coverage(string $name, string $unit, float $need, Collection $donations): array
    {
        $key = $this->key($name, $unit);
        $covered = round($donations
            ->filter(fn (Donation $donation): bool => $this->donationKey($donation) === $key && (float) $donation->quantity > 0)
            ->sum(fn (Donation $donation): float => (float) $donation->quantity), 3);
        $remaining = round(max(0.0, $need - $covered), 3);

        $needLabel = PracticalQuantity::format($need, $unit);
        $coveredLabel = PracticalQuantity::format($covered, $unit);

        $label = $remaining > 0
            ? sprintf('Necesar %s, acoperit %s, mai trebuie %s', $needLabel, $coveredLabel, PracticalQuantity::format($remaining, $unit))
            : sprintf('Necesar %s, acoperit %s, acoperit integral', $needLabel, $coveredLabel);

        if ($covered > $need) {
            $label .= ' (cu '.PracticalQuantity::format(round($covered - $need, 3), $unit).' peste necesar)';
        }

        return [
            'name' => $name,
            'unit' => $unit,
            'need' => $need,
            'covered' => $covered,
            'remaining' => $remaining,
            'label' => $label,
        ];
    }

    private function donationsFor(DailyMeal $meal): Collection
    {
        $date = $meal->meal_date?->toDateString();

        return Donation::query()
            ->where('kind', Donation::KIND_FOOD)
            ->where('status', '!=', Donation::STATUS_CANCELLED)
            ->where(function ($query) use ($meal, $date): void {
                $query->where('daily_meal_id', $meal->id);

                if ($date !== null) {
                    $query->orWhereDate('date', $date)
                        ->orWhereDate('donation_date', $date);
                }
            })
            ->get(['description', 'quantity', 'unit']);
    }

    private function donationKey(Donation $donation): string
    {
        return $this->key((string) $donation->description, (string) $donation->unit);
    }

    private function key(string $name, string $unit): string
    {
        return $this->normalize($name).'|'.$this->normalize($unit);
    }

    private function normalize(string $value): string
    {
        return (string) preg_replace('/\s+/', ' ', Str::ascii(mb_strtolower(trim($value)))) ?? '';
    }
}
