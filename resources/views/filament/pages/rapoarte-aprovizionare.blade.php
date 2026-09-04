<x-filament-panels::page>
    <style>
        .supply-report { --report-line: rgb(148 163 184 / .2); --report-muted: rgb(148 163 184); }
        .supply-report__hero { background: linear-gradient(135deg, rgb(16 185 129 / .16), rgb(15 23 42 / .08)); border: 1px solid rgb(16 185 129 / .25); border-radius: 18px; padding: 28px 30px; }
        .supply-report__hero h1 { font-size: clamp(1.8rem, 3vw, 2.5rem); letter-spacing: -.04em; }
        .supply-report__layout { align-items: start; display: grid; gap: 24px; grid-template-columns: minmax(260px, 320px) minmax(0, 1fr); }
        .supply-report__control { display: grid; gap: 18px; position: sticky; top: 20px; }
        .supply-report__control .supply-report__filters { display: grid; grid-template-columns: 1fr; }
        .supply-report__overview { display: grid; gap: 12px; grid-template-columns: 1fr 1fr; }
        .supply-report__overview .supply-report__kpi { min-height: 155px; padding: 17px; }
        .supply-report__overview .supply-report__kpi-icon { font-size: 1.8rem; }
        .supply-report__overview .supply-report__kpi-value { font-size: 1.6rem; margin-top: 12px; }
        .supply-report__analysis { display: grid; gap: 18px; min-width: 0; }
        .supply-report__filters { align-items: end; display: grid; gap: 12px; grid-template-columns: 1fr 1.5fr 1.6fr auto; }
        .supply-report__filter { min-width: 0; }
        .supply-report__filter label { color: var(--report-muted); display: block; font-size: .7rem; font-weight: 700; letter-spacing: .06em; margin-bottom: 6px; text-transform: uppercase; }
        .supply-report__kpi { border: 1px solid var(--report-line); border-radius: 16px; min-height: 170px; padding: 22px; position: relative; box-shadow: 0 4px 16px rgb(15 23 42 / .08); }
        .supply-report__kpi-icon { font-size: 2.2rem; line-height: 1; }
        .supply-report__kpi-value { font-size: clamp(1.8rem, 3vw, 2.5rem); font-weight: 800; letter-spacing: -.04em; margin-top: 18px; }
        .supply-report__kpi-label { color: var(--report-muted); font-size: .85rem; font-weight: 700; margin-top: 12px; }
        .supply-report__kpi-sub { color: var(--report-muted); font-size: .75rem; margin-top: 4px; }
        .supply-report__table { border: 1px solid var(--report-line); border-radius: 14px; overflow: hidden; }
        .supply-report__table table { border-collapse: collapse; min-width: 800px; width: 100%; }
        .supply-report__table th { background: rgb(100 116 139 / .14); color: var(--report-muted); font-size: .7rem; letter-spacing: .04em; padding: 13px 14px; text-transform: uppercase; }
        .supply-report__table td { border-top: 1px solid var(--report-line); padding: 13px 14px; vertical-align: middle; }
        .supply-report__table tbody tr:nth-child(even) { background: rgb(100 116 139 / .055); }
        .supply-report__alert { align-items: center; border: 1px solid; border-radius: 12px; display: flex; gap: 10px; padding: 13px 15px; }
        .supply-report__alert--danger { background: rgb(244 63 94 / .1); border-color: rgb(244 63 94 / .3); }
        .supply-report__alert--warning { background: rgb(245 158 11 / .1); border-color: rgb(245 158 11 / .3); }
        .supply-report__alert--info { background: rgb(14 165 233 / .1); border-color: rgb(14 165 233 / .3); }
        .supply-report__chart { border: 1px solid var(--report-line); border-radius: 14px; padding: 20px; }
        @media (max-width: 900px) { .supply-report__filters { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 1100px) { .supply-report__layout { grid-template-columns: 1fr; } .supply-report__control { position: static; } .supply-report__control .supply-report__filters { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        @media (max-width: 700px) { .supply-report__filters, .supply-report__control .supply-report__filters { grid-template-columns: 1fr; } .supply-report__overview { grid-template-columns: 1fr 1fr; } .supply-report__hero { padding: 22px; } }
        @media (max-width: 420px) { .supply-report__overview { grid-template-columns: 1fr; } }
    </style>
    <div class="supply-report space-y-6">
        <div class="supply-report__hero flex flex-wrap items-end justify-between gap-5">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-600">📦 Control operational</p>
                <h1 class="mt-2 font-bold">Rapoarte aprovizionare</h1>
                <p class="mt-2 text-sm text-gray-500">Overview & detaliu pentru perioada selectata.</p>
            </div>
            <div class="flex gap-2">
                <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" disabled>Export PDF</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-o-table-cells" disabled>Export CSV/XLSX</x-filament::button>
            </div>
        </div>

        <div class="supply-report__layout">
            <aside class="supply-report__control">
                <x-filament::section heading="Control raport">
            <div class="supply-report__filters">
                <div class="supply-report__filter">
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Interval</label>
                    <select wire:model="period" class="fi-select-input block w-full rounded-lg border-gray-300 bg-white dark:border-white/10 dark:bg-white/5">
                        <option value="day">O zi</option><option value="week">O saptamana</option><option value="month">O luna</option>
                    </select>
                </div>
                @if ($period === 'week')
                    <div class="supply-report__filter">
                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Saptamana</label>
                        <select wire:model="weekId" class="fi-select-input block w-full rounded-lg border-gray-300 bg-white dark:border-white/10 dark:bg-white/5">
                            @foreach (App\Models\Week::query()->orderBy('week_number')->get() as $week)
                                <option value="{{ $week->id }}">Saptamana {{ $week->week_number }} ({{ $week->start_date->format('d.m.Y') }})</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="supply-report__filter">
                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Data de referinta</label>
                        <input type="{{ $period === 'month' ? 'month' : 'date' }}" wire:model="selectedDate" class="fi-input block w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5">
                    </div>
                @endif
                <div class="supply-report__filter">
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-500">Tip raport</label>
                    <select wire:model="reportType" class="fi-select-input block w-full rounded-lg border-gray-300 bg-white dark:border-white/10 dark:bg-white/5">
                        <option value="supplies">Necesar si aprovizionare</option><option value="contributions">Aprovizionare / contributii</option><option value="stock">Verificare stoc</option>
                    </select>
                </div>
                <x-filament::button color="warning" icon="heroicon-o-arrow-path" wire:click="generateReport">Genereaza raport</x-filament::button>
            </div>
                </x-filament::section>

        @php($totals = $this->totals)
        @php($waterRatio = $totals['water_required'] > 0 ? min(100, ($totals['water_confirmed'] / $totals['water_required']) * 100) : 100)
        @php($snackRatio = $totals['snacks_required'] > 0 ? min(100, ($totals['snacks_confirmed'] / $totals['snacks_required']) * 100) : 100)
        @php($dessertRatio = $totals['desserts_required'] > 0 ? min(100, ($totals['desserts_confirmed'] / $totals['desserts_required']) * 100) : 100)
        <div class="supply-report__overview">
            @foreach ([
                ['icon' => '👥', 'label' => 'Persoane programate', 'value' => $totals['people'], 'sub' => 'in perioada selectata', 'color' => 'primary'],
                ['icon' => '💧', 'label' => 'Necesar apa', 'value' => $this->formatQuantity($totals['water_required']).' L', 'sub' => 'Confirmat: '.$this->formatQuantity($totals['water_confirmed']).' L', 'color' => $waterRatio >= 100 ? 'success' : ($waterRatio >= 70 ? 'warning' : 'danger')],
                ['icon' => '🍪', 'label' => 'Gustari necesare', 'value' => $this->formatQuantity($totals['snacks_required']).' portii', 'sub' => 'Confirmat: '.$this->formatQuantity($totals['snacks_confirmed']).' portii', 'color' => $snackRatio >= 100 ? 'success' : ($snackRatio >= 70 ? 'warning' : 'danger')],
                ['icon' => '🍰', 'label' => 'Deserturi necesare', 'value' => $this->formatQuantity($totals['desserts_required']).' portii', 'sub' => 'Confirmat: '.$this->formatQuantity($totals['desserts_confirmed']).' portii', 'color' => $dessertRatio >= 100 ? 'success' : ($dessertRatio >= 70 ? 'warning' : 'danger')],
            ] as $card)
                <div class="supply-report__kpi bg-white dark:bg-white/5">
                    <div class="flex items-start justify-between"><span class="supply-report__kpi-icon">{{ $card['icon'] }}</span><span class="h-3 w-3 rounded-full bg-{{ $card['color'] }}-500"></span></div>
                    <p class="supply-report__kpi-label">{{ $card['label'] }}</p>
                    <p class="supply-report__kpi-value">{{ $card['value'] }}</p>
                    <p class="supply-report__kpi-sub">{{ $card['sub'] }}</p>
                </div>
            @endforeach
        </div>
            </aside>

            <main class="supply-report__analysis">
        @if ($reportType === 'stock')
            <x-filament::section heading="Verificare stoc si aprovizionare">
                <div class="supply-report__table overflow-x-auto"><table class="text-sm"><thead><tr class="text-left"><th class="p-3">Consumabil</th><th class="p-3">Categorie</th><th class="p-3">Stoc</th><th class="p-3">Minim</th><th class="p-3">Consum/zi</th><th class="p-3">Actiune</th></tr></thead><tbody>
                    @foreach ($this->stockItems as $item)
                        <tr class="border-b odd:bg-gray-50 dark:odd:bg-white/5"><td class="p-3 font-medium">{{ $item->name }}</td><td class="p-3">{{ $item->category }}</td><td class="p-3">{{ $this->formatQuantity((float) $item->current_stock) }} {{ $item->unit }}</td><td class="p-3">{{ $this->formatQuantity((float) $item->minimum_stock) }} {{ $item->unit }}</td><td class="p-3">{{ $this->formatQuantity((float) $item->estimated_daily_consumption) }} {{ $item->unit }}</td><td class="p-3 font-semibold {{ $item->isBelowMinimum() ? 'text-danger-600' : 'text-success-600' }}">{{ $item->isBelowMinimum() ? 'De aprovizionat' : 'In regula' }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </x-filament::section>
        @elseif ($reportType === 'contributions')
            <x-filament::section heading="Contributii in perioada selectata">
                <div class="supply-report__table overflow-x-auto"><table class="text-sm"><thead><tr class="text-left"><th class="p-3">Data</th><th class="p-3">Congregatie</th><th class="p-3">Resursa</th><th class="p-3">Cantitate</th><th class="p-3">Status</th><th class="p-3">Responsabil</th></tr></thead><tbody>
                    @forelse ($this->contributions as $contribution)
                        <tr><td class="p-3">{{ $contribution->delivery_date->format('d.m.Y') }}</td><td class="p-3">{{ $contribution->congregation?->name }}</td><td class="p-3">{{ $contribution->supplyItem?->name }}</td><td class="p-3">{{ $this->formatQuantity((float) $contribution->quantity) }} {{ $contribution->supplyItem?->unit }}</td><td class="p-3">{{ $contribution->delivery_status }}</td><td class="p-3">{{ $contribution->responsible_name ?: '-' }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-gray-500">Nu exista contributii in perioada selectata.</td></tr>
                    @endforelse
                </tbody></table></div>
            </x-filament::section>
        @else
            <x-filament::section heading="Necesar si aprovizionare pe zile">
                <div class="supply-report__table overflow-x-auto"><table class="text-sm"><thead><tr class="text-left"><th class="p-3">Data</th><th class="p-3">Persoane</th><th class="p-3">Apa necesara</th><th class="p-3">Apa confirmata</th><th class="p-3">Diferenta</th><th class="p-3">Gustari necesare / confirmate</th><th class="p-3">Deserturi necesare / confirmate</th></tr></thead><tbody>
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
                <div class="supply-report__alert supply-report__alert--{{ $alert['type'] }} mb-2 text-sm"><span class="text-lg">{{ $alert['type'] === 'danger' ? '🔴' : ($alert['type'] === 'warning' ? '🟡' : '🔵') }}</span><span>{{ $alert['text'] }}</span></div>
            @empty
                <p class="text-sm text-success-600">✅ Nu exista alerte pentru perioada selectata.</p>
            @endforelse
        </x-filament::section>

        <x-filament::section heading="Consum vs aprovizionare">
            <div class="supply-report__chart space-y-4">
                @foreach ($this->chartData as $chart)
                    @php($max = max($chart['water_required'], $chart['snacks_required'], $chart['desserts_required'], 1))
                    <div><div class="mb-1 flex justify-between text-xs text-gray-500"><span>{{ $chart['label'] }}</span><span>Apa / Gustari / Deserturi</span></div><div class="flex h-5 gap-1"><div class="rounded bg-primary-500" style="width: {{ ($chart['water_required'] / $max) * 100 }}%" title="Apa necesara"></div><div class="rounded bg-warning-500" style="width: {{ ($chart['snacks_required'] / $max) * 100 }}%" title="Gustari necesare"></div><div class="rounded bg-danger-500" style="width: {{ ($chart['desserts_required'] / $max) * 100 }}%" title="Deserturi necesare"></div></div></div>
                @endforeach
            </div>
        </x-filament::section>
            </main>
        </div>
    </div>
</x-filament-panels::page>
