<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Scade din lista de cumparaturi doar donatiile care sunt chiar alimente primite
 * si au descrierea egala cu un ingredient din lista. Promisiunile, banii,
 * alte donatii si valorile care nu pot fi identificate ca ingredient raman
 * doar in totalurile financiare.
 */
class DonationDeductionService
{
    /**
     * @param  list<array<string, mixed>>  $ingredients
     * @param  Collection<int, Donation>|list<Donation>  $donations
     * @return array{ingredients: list<array<string, mixed>>, deductions: list<array{donation_id: int, ingredient: string, quantity: float, unit: string}>}
     */
    public function apply(array $ingredients, Collection|array $donations): array
    {
        $available = collect($donations)
            ->filter(fn (Donation $donation): bool => $donation->kind === Donation::KIND_FOOD
                && $donation->status === Donation::STATUS_RECEIVED
                && filled($donation->description)
                && (float) $donation->quantity > 0)
            ->groupBy(fn (Donation $donation): string => $this->key((string) $donation->description, (string) $donation->unit))
            ->map(fn (Collection $group): array => $group->map(fn (Donation $donation): array => [
                'donation' => $donation,
                'remaining' => (float) $donation->quantity,
            ])->all());

        $deductions = [];
        $adjusted = [];

        foreach ($ingredients as $ingredient) {
            $key = $this->key((string) $ingredient['name'], (string) ($ingredient['unit'] ?? ''));
            $toBuy = (float) ($ingredient['to_buy_quantity'] ?? $ingredient['quantity']);
            $needed = $toBuy;
            $unitCost = $ingredient['estimated_unit_cost'] ?? null;

            while ($needed > 0.0001 && ! empty($available->get($key, []))) {
                $entries = $available->get($key, []);
                $entry = array_shift($entries);
                $available->put($key, $entries);

                if ($entry['remaining'] <= 0.0001) {
                    continue;
                }

                $quantity = min($needed, $entry['remaining']);
                $entry['remaining'] -= $quantity;
                $needed -= $quantity;

                if ($entry['remaining'] > 0.0001) {
                    $entries[] = $entry;
                }

                $available->put($key, $entries);

                $deductions[] = [
                    'donation_id' => (int) $entry['donation']->id,
                    'ingredient' => (string) $ingredient['name'],
                    'quantity' => round($quantity, 3),
                    'unit' => (string) ($ingredient['unit'] ?? ''),
                ];
            }

            $donated = round($needed > 0.0001 ? $toBuy - $needed : $toBuy, 3);

            if ($donated > 0) {
                $ingredient['donated_quantity'] = $donated;
                $ingredient['to_buy_quantity'] = round(max(0.0, $toBuy - $donated), 3);

                if (is_numeric($unitCost)) {
                    $ingredient['estimated_cost'] = round(
                        max(0.0, (float) $ingredient['estimated_cost'] - $donated * (float) $unitCost),
                        2,
                    );
                }
            }

            $adjusted[] = $ingredient;
        }

        return [
            'ingredients' => $adjusted,
            'deductions' => $deductions,
        ];
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
