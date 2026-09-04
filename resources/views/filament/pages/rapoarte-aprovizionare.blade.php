<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-600">📦 Control operational</p>
                <h1 class="text-3xl font-bold tracking-tight">Rapoarte aprovizionare</h1>
                <p class="mt-1 text-sm text-gray-500">Overview si detaliu pentru perioada selectata.</p>
            </div>
            <div class="flex gap-2">
                <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" disabled>Export PDF</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-o-table-cells" disabled>Export CSV/XLSX</x-filament::button>
            </div>
        </div>

        <x-filament::section>
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-36 flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Interval</label>
                    <select wire:model="period" class="fi-select-input block w-full rounded-lg border-gray-300 bg-white dark:border-white/10 dark:bg-white/5">
                        <option value="day">O zi</option><option value="week">O saptamana</option><option value="month">O luna</option>
                    </select>
                </div>
                @if ($period === 'week')
                    <div class="min-w-52 flex-1">
                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Saptamana</label>
                        <select wire:model="weekId" class="fi-select-input block w-full rounded-lg border-gray-300 bg-white dark:border-white/10 dark:bg-white/5">
                            @foreach (App\Models\Week::query()->orderBy('week_number')->get() as $week)
                                <option value="{{ $week->id }}">Saptamana {{ $week->week_number }} ({{ $week->start_date->format('d.m.Y') }})</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="min-w-44 flex-1">
                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Data de referinta</label>
                        <input type="{{ $period === 'month' ? 'month' : 'date' }}" wire:model="selectedDate" class="fi-input block w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5">
                    </div>
                @endif
                <div class="min-w-52 flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Tip raport</label>
                    <select wire:model="reportType" class="fi-select-input block w-full rounded-lg border-gray-300 bg-white dark:border-white/10 dark:bg-white/5">
                        <option value="supplies">Necesar si aprovizionare</option><option value="contributions">Aprovizionare / contributii</option><option value="stock">Verificare stoc</option>
                    </select>
                </div>
                <x-filament::button icon="heroicon-o-arrow-path" wire:click="generateReport">Genereaza raport</x-filament::button>
            </div>
        </x-filament::section>

        @php($totals = $this->totals)
        @php($waterRatio = $totals['water_required'] > 0 ? min(100, ($totals['water_confirmed'] / $totals['water_required']) * 100) : 100)
        @php($snackRatio = $totals['snacks_required'] > 0 ? min(100, ($totals['snacks_confirmed'] / $totals['snacks_required']) * 100) : 100)
        @php($dessertRatio = $totals['desserts_required'] > 0 ? min(100, ($totals['desserts_confirmed'] / $totals['desserts_required']) * 100) : 100)
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['icon' => '👥', 'label' => 'Persoane programate', 'value' => $totals['people'], 'sub' => 'in perioada selectata', 'color' => 'primary'],
                ['icon' => '💧', 'label' => 'Necesar apa', 'value' => $this->formatQuantity($totals['water_required']).' L', 'sub' => 'Confirmat: '.$this->formatQuantity($totals['water_confirmed']).' L', 'color' => $waterRatio >= 100 ? 'success' : ($waterRatio >= 70 ? 'warning' : 'danger')],
                ['icon' => '🍪', 'label' => 'Gustari necesare', 'value' => $this->formatQuantity($totals['snacks_required']).' portii', 'sub' => 'Confirmat: '.$this->formatQuantity($totals['snacks_confirmed']).' portii', 'color' => $snackRatio >= 100 ? 'success' : ($snackRatio >= 70 ? 'warning' : 'danger')],
                ['icon' => '🍰', 'label' => 'Deserturi necesare', 'value' => $this->formatQuantity($totals['desserts_required']).' portii', 'sub' => 'Confirmat: '.$this->formatQuantity($totals['desserts_confirmed']).' portii', 'color' => $dessertRatio >= 100 ? 'success' : ($dessertRatio >= 70 ? 'warning' : 'danger')],
            ] as $card)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <div class="flex items-start justify-between"><span class="text-2xl">{{ $card['icon'] }}</span><span class="h-2.5 w-2.5 rounded-full bg-{{ $card['color'] }}-500"></span></div>
                    <p class="mt-4 text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold">{{ $card['value'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $card['sub'] }}</p>
                </div>
            @endforeach
        </div>

        @if ($reportType === 'stock')
            <x-filament::section heading="Verificare stoc si aprovizionare">
                <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="p-3">Consumabil</th><th class="p-3">Categorie</th><th class="p-3">Stoc</th><th class="p-3">Minim</th><th class="p-3">Consum/zi</th><th class="p-3">Actiune</th></tr></thead><tbody>
                    @foreach ($this->stockItems as $item)
                        <tr class="border-b odd:bg-gray-50 dark:odd:bg-white/5"><td class="p-3 font-medium">{{ $item->name }}</td><td class="p-3">{{ $item->category }}</td><td class="p-3">{{ $this->formatQuantity((float) $item->current_stock) }} {{ $item->unit }}</td><td class="p-3">{{ $this->formatQuantity((float) $item->minimum_stock) }} {{ $item->unit }}</td><td class="p-3">{{ $this->formatQuantity((float) $item->estimated_daily_consumption) }} {{ $item->unit }}</td><td class="p-3 font-semibold {{ $item->isBelowMinimum() ? 'text-danger-600' : 'text-success-600' }}">{{ $item->isBelowMinimum() ? 'De aprovizionat' : 'In regula' }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </x-filament::section>
        @elseif ($reportType === 'contributions')
            <x-filament::section heading="Contributii in perioada selectata">
                <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="p-3">Data</th><th class="p-3">Congregatie</th><th class="p-3">Resursa</th><th class="p-3">Cantitate</th><th class="p-3">Status</th><th class="p-3">Responsabil</th></tr></thead><tbody>
                    @forelse ($this->contributions as $contribution)
                        <tr class="border-b odd:bg-gray-50 dark:odd:bg-white/5"><td class="p-3">{{ $contribution->delivery_date->format('d.m.Y') }}</td><td class="p-3">{{ $contribution->congregation?->name }}</td><td class="p-3">{{ $contribution->supplyItem?->name }}</td><td class="p-3">{{ $this->formatQuantity((float) $contribution->quantity) }} {{ $contribution->supplyItem?->unit }}</td><td class="p-3">{{ $contribution->delivery_status }}</td><td class="p-3">{{ $contribution->responsible_name ?: '-' }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-gray-500">Nu exista contributii in perioada selectata.</td></tr>
                    @endforelse
                </tbody></table></div>
            </x-filament::section>
        @else
            <x-filament::section heading="Necesar si aprovizionare pe zile">
                <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="p-3">Data</th><th class="p-3">Persoane</th><th class="p-3">Apa necesara</th><th class="p-3">Apa confirmata</th><th class="p-3">Diferenta</th><th class="p-3">Gustari necesare / confirmate</th><th class="p-3">Deserturi necesare / confirmate</th></tr></thead><tbody>
                    @forelse ($this->plans as $plan)
                        @php($waterDifference = $plan->toBuy('still_water') + $plan->toBuy('mineral_water'))
                        @php($snackDifference = $plan->toBuy('snacks'))
                        @php($dessertDifference = $plan->toBuy('desserts'))
                        <tr class="border-b odd:bg-gray-50 dark:odd:bg-white/5" title="Contributii: {{ $this->contributions->where('delivery_date', $plan->plan_date)->pluck('congregation.name')->filter()->unique()->implode(', ') ?: 'Nicio contributie inregistrata' }}">
                            <td class="p-3 font-medium">{{ $plan->plan_date->format('d.m.Y') }}</td><td class="p-3">{{ $plan->people_count }}</td><td class="p-3">{{ $this->formatQuantity((float) $plan->still_water_required + (float) $plan->mineral_water_required) }} L</td><td class="p-3">{{ $this->formatQuantity((float) $plan->still_water_confirmed + (float) $plan->mineral_water_confirmed) }} L</td><td class="p-3 font-semibold {{ $waterDifference > 0 ? 'text-danger-600' : 'text-success-600' }}">{{ $this->formatQuantity($waterDifference) }} L</td><td class="p-3">{{ $this->formatQuantity((float) $plan->snacks_required) }} / {{ $this->formatQuantity((float) $plan->snacks_confirmed) }} <span class="{{ $snackDifference > 0 ? 'text-warning-600' : 'text-success-600' }}">({{ $this->formatQuantity($snackDifference) }})</span></td><td class="p-3">{{ $this->formatQuantity((float) $plan->desserts_required) }} / {{ $this->formatQuantity((float) $plan->desserts_confirmed) }} <span class="{{ $dessertDifference > 0 ? 'text-warning-600' : 'text-success-600' }}">({{ $this->formatQuantity($dessertDifference) }})</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6 text-center text-gray-500">Nu exista planuri in perioada selectata.</td></tr>
                    @endforelse
                </tbody></table></div>
            </x-filament::section>
        @endif

        <x-filament::section heading="Alerte & recomandari">
            @forelse ($this->alerts as $alert)
                <div class="mb-2 rounded-lg border p-3 text-sm {{ $alert['type'] === 'danger' ? 'border-danger-200 bg-danger-50 text-danger-700' : ($alert['type'] === 'warning' ? 'border-warning-200 bg-warning-50 text-warning-700' : 'border-info-200 bg-info-50 text-info-700') }}">{{ $alert['type'] === 'danger' ? '🔴' : ($alert['type'] === 'warning' ? '🟡' : '🔵') }} {{ $alert['text'] }}</div>
            @empty
                <p class="text-sm text-success-600">✅ Nu exista alerte pentru perioada selectata.</p>
            @endforelse
        </x-filament::section>

        <x-filament::section heading="Consum vs aprovizionare">
            <div class="space-y-4">
                @foreach ($this->chartData as $chart)
                    @php($max = max($chart['water_required'], $chart['snacks_required'], $chart['desserts_required'], 1))
                    <div><div class="mb-1 flex justify-between text-xs text-gray-500"><span>{{ $chart['label'] }}</span><span>Apa / Gustari / Deserturi</span></div><div class="flex h-5 gap-1"><div class="rounded bg-primary-500" style="width: {{ ($chart['water_required'] / $max) * 100 }}%" title="Apa necesara"></div><div class="rounded bg-warning-500" style="width: {{ ($chart['snacks_required'] / $max) * 100 }}%" title="Gustari necesare"></div><div class="rounded bg-danger-500" style="width: {{ ($chart['desserts_required'] / $max) * 100 }}%" title="Deserturi necesare"></div></div></div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
