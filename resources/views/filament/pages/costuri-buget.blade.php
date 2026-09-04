<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Costuri & buget operational">
            <div class="flex items-end gap-3">
                <div class="max-w-sm flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Saptamana</label>
                    <select wire:model.live="weekId" class="fi-select-input block w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5">
                        @foreach (App\Models\Week::query()->orderBy('week_number')->get() as $week)
                            <option value="{{ $week->id }}">Saptamana {{ $week->week_number }} ({{ $week->start_date->format('d.m.Y') }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament::section>
        @php($weeklyTotal = $this->costs->sum('cost'))
        <div class="grid gap-4 md:grid-cols-4">
            @foreach ([
                ['label' => 'Cost saptamana', 'value' => number_format($weeklyTotal, 2, '.', ' ').' RON'],
                ['label' => 'Cost mediu / zi', 'value' => number_format($this->costs->avg('cost') ?: 0, 2, '.', ' ').' RON'],
                ['label' => 'Cost mediu / voluntar', 'value' => number_format($this->costs->sum('people') > 0 ? $weeklyTotal / $this->costs->sum('people') : 0, 2, '.', ' ').' RON'],
                ['label' => 'Valoare stoc actual', 'value' => number_format($this->supplyCost, 2, '.', ' ').' RON'],
            ] as $card)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5"><p class="text-sm text-gray-500">{{ $card['label'] }}</p><p class="mt-2 text-2xl font-bold">{{ $card['value'] }}</p></div>
            @endforeach
        </div>
        <x-filament::section heading="Cost pe zi si meniu">
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="p-3">Data</th><th class="p-3">Persoane</th><th class="p-3">Cost total</th><th class="p-3">Cost / persoana</th><th class="p-3">Status</th></tr></thead><tbody>
                @forelse ($this->costs as $cost)
                    <tr class="border-b odd:bg-gray-50 dark:odd:bg-white/5"><td class="p-3">{{ $cost['date']->format('d.m.Y') }}</td><td class="p-3">{{ $cost['people'] }}</td><td class="p-3">{{ number_format($cost['cost'], 2, '.', ' ') }} RON</td><td class="p-3">{{ number_format($cost['per_person'], 2, '.', ' ') }} RON</td><td class="p-3">{{ $cost['missing'] ? 'Preturi incomplete' : 'Calculat' }}</td></tr>
                @empty
                    <tr><td colspan="5" class="p-4">Nu exista mese pentru saptamana selectata.</td></tr>
                @endforelse
            </tbody></table></div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
