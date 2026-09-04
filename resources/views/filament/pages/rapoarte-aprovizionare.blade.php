<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Filtre raport</x-slot>
            <div class="grid gap-4 md:grid-cols-4">
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="period" aria-label="Perioada">
                        <option value="day">O zi</option>
                        <option value="week">O saptamana</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                @if ($period === 'day')
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="selectedDate" aria-label="Data raportului" />
                    </x-filament::input.wrapper>
                @else
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="weekId" aria-label="Saptamana raportului">
                            @foreach (App\Models\Week::query()->orderBy('week_number')->get() as $week)
                                <option value="{{ $week->id }}">Saptamana {{ $week->week_number }} ({{ $week->start_date->format('d.m.Y') }})</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                @endif
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="reportType" aria-label="Tip raport">
                        <option value="supplies">Necesar si aprovizionare</option>
                        <option value="contributions">Contributii congregatii</option>
                        <option value="stock">Verificare stoc</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </x-filament::section>

        @php($totals = $this->totals)
        @if ($reportType !== 'stock')
            <div class="grid gap-4 md:grid-cols-4">
                @foreach ([
                    'Persoane' => $totals['people'],
                    'Apa necesara' => $this->formatQuantity($totals['water_required']).' L',
                    'Apa confirmata' => $this->formatQuantity($totals['water_confirmed']).' L',
                    'Gustari de cumparat' => $this->formatQuantity(max(0, $totals['snacks_required'] - $totals['snacks_confirmed'])).' portii',
                ] as $label => $value)
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($reportType === 'contributions')
            <x-filament::section heading="Contributii in perioada selectata">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left"><th class="p-2">Data</th><th class="p-2">Congregatie</th><th class="p-2">Resursa</th><th class="p-2">Cantitate</th><th class="p-2">Status</th><th class="p-2">Responsabil</th></tr></thead>
                        <tbody>
                            @forelse ($this->contributions as $contribution)
                                <tr class="border-t"><td class="p-2">{{ $contribution->delivery_date->format('d.m.Y') }}</td><td class="p-2">{{ $contribution->congregation?->name }}</td><td class="p-2">{{ $contribution->supplyItem?->name }}</td><td class="p-2">{{ $this->formatQuantity((float) $contribution->quantity) }} {{ $contribution->supplyItem?->unit }}</td><td class="p-2">{{ $contribution->delivery_status }}</td><td class="p-2">{{ $contribution->responsible_name ?: '-' }}</td></tr>
                            @empty
                                <tr><td colspan="6" class="p-4">Nu exista contributii in perioada selectata.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @elseif ($reportType === 'stock')
            <x-filament::section heading="Verificare stoc si aprovizionare">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left"><th class="p-2">Consumabil</th><th class="p-2">Categorie</th><th class="p-2">Stoc actual</th><th class="p-2">Minim</th><th class="p-2">Consum estimat/zi</th><th class="p-2">Actiune</th></tr></thead>
                        <tbody>
                            @foreach ($this->stockItems as $item)
                                <tr class="border-t"><td class="p-2">{{ $item->name }}</td><td class="p-2">{{ $item->category }}</td><td class="p-2">{{ $this->formatQuantity((float) $item->current_stock) }} {{ $item->unit }}</td><td class="p-2">{{ $this->formatQuantity((float) $item->minimum_stock) }} {{ $item->unit }}</td><td class="p-2">{{ $this->formatQuantity((float) $item->estimated_daily_consumption) }} {{ $item->unit }}</td><td class="p-2">{{ $item->isBelowMinimum() ? 'De aprovizionat' : 'In regula' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @else
            <x-filament::section heading="Necesar si aprovizionare pe zile">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left"><th class="p-2">Data</th><th class="p-2">Persoane</th><th class="p-2">Apa de cumparat</th><th class="p-2">Gustari de cumparat</th><th class="p-2">Deserturi de cumparat</th></tr></thead>
                        <tbody>
                            @forelse ($this->plans as $plan)
                                <tr class="border-t"><td class="p-2">{{ $plan->plan_date->format('d.m.Y') }}</td><td class="p-2">{{ $plan->people_count }}</td><td class="p-2">{{ $this->formatQuantity($plan->toBuy('still_water') + $plan->toBuy('mineral_water')) }} L</td><td class="p-2">{{ $this->formatQuantity($plan->toBuy('snacks')) }} portii</td><td class="p-2">{{ $this->formatQuantity($plan->toBuy('desserts')) }} portii</td></tr>
                            @empty
                                <tr><td colspan="5" class="p-4">Nu exista plan de aprovizionare in perioada selectata.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
