<x-filament-panels::page>
    <style>
        .budget-page { --budget-line: rgb(148 163 184 / .2); --budget-muted: rgb(100 116 139); }
        .budget-hero { align-items: end; background: linear-gradient(135deg, rgb(245 158 11 / .16), rgb(15 23 42 / .06)); border: 1px solid rgb(245 158 11 / .28); border-radius: 18px; display: flex; gap: 20px; justify-content: space-between; padding: 28px 30px; }
        .budget-hero h1 { font-size: clamp(1.8rem, 3vw, 2.5rem); letter-spacing: -.04em; }
        .budget-filter { align-items: end; display: grid; gap: 14px; grid-template-columns: minmax(0, 1fr) auto; }
        .budget-filter label, .budget-kpi-label { color: var(--budget-muted); font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .budget-select { background: white; border: 1px solid var(--budget-line); border-radius: 10px; display: block; font-size: .9rem; margin-top: 7px; min-height: 42px; padding: 0 38px 0 12px; width: 100%; }
        .budget-kpis { display: grid; gap: 14px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .budget-kpi { border: 1px solid var(--budget-line); border-radius: 16px; min-height: 142px; padding: 20px; position: relative; box-shadow: 0 4px 16px rgb(15 23 42 / .07); }
        .budget-kpi-icon { font-size: 1.8rem; line-height: 1; }
        .budget-kpi-value { font-size: clamp(1.5rem, 2.5vw, 2.15rem); font-weight: 800; letter-spacing: -.04em; margin-top: 17px; }
        .budget-kpi--primary { background: linear-gradient(135deg, rgb(245 158 11 / .17), transparent); }
        .budget-table-wrap { border: 1px solid var(--budget-line); border-radius: 14px; overflow-x: auto; }
        .budget-table { border-collapse: collapse; min-width: 680px; width: 100%; }
        .budget-table th { background: rgb(100 116 139 / .12); color: var(--budget-muted); font-size: .7rem; letter-spacing: .05em; padding: 14px 16px; text-align: left; text-transform: uppercase; }
        .budget-table td { border-top: 1px solid var(--budget-line); padding: 15px 16px; vertical-align: middle; }
        .budget-table tbody tr:nth-child(even) { background: rgb(100 116 139 / .045); }
        .budget-status { border-radius: 999px; display: inline-flex; font-size: .72rem; font-weight: 700; padding: 5px 9px; }
        .budget-status--ok { background: rgb(16 185 129 / .13); color: rgb(4 120 87); }
        .budget-status--missing { background: rgb(245 158 11 / .15); color: rgb(180 83 9); }
        @media (max-width: 900px) { .budget-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 600px) { .budget-hero { align-items: start; flex-direction: column; padding: 22px; } .budget-filter { grid-template-columns: 1fr; } .budget-kpis { grid-template-columns: 1fr 1fr; } }
    </style>

    <div class="budget-page space-y-6">
        <header class="budget-hero">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-warning-600">💰 Control financiar</p>
                <h1 class="mt-2 font-bold">Costuri & buget</h1>
                <p class="mt-2 text-sm text-gray-500">Urmareste rapid costurile meniurilor si valoarea stocului.</p>
            </div>
            <div class="hidden text-right text-sm text-gray-500 md:block">
                <span class="block font-semibold text-gray-700 dark:text-gray-200">Perioada selectata</span>
                {{ $this->week?->start_date?->format('d.m.Y') ?? 'Neselectata' }}
            </div>
        </header>

        <x-filament::section heading="Perioada de analiza">
            <div class="budget-filter">
                <div>
                    <label for="budget-week">Saptamana</label>
                    <select id="budget-week" wire:model.live="weekId" class="budget-select">
                        @foreach ($this->weeks as $week)
                            <option value="{{ $week->id }}">Saptamana {{ $week->week_number }} · {{ $week->start_date->format('d.m.Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="text-sm text-gray-500">
                    {{ $this->costs->count() }} {{ $this->costs->count() === 1 ? 'zi planificata' : 'zile planificate' }}
                </div>
            </div>
        </x-filament::section>

        @php($weeklyTotal = $this->costs->sum('cost'))
        @php($peopleTotal = $this->costs->sum('people'))
        <div class="budget-kpis">
            @foreach ([
                ['icon' => '📊', 'label' => 'Cost saptamana', 'value' => number_format($weeklyTotal, 2, '.', ' ').' RON', 'class' => 'budget-kpi--primary'],
                ['icon' => '📅', 'label' => 'Cost mediu / zi', 'value' => number_format($this->costs->avg('cost') ?: 0, 2, '.', ' ').' RON', 'class' => ''],
                ['icon' => '👥', 'label' => 'Cost mediu / voluntar', 'value' => number_format($peopleTotal > 0 ? $weeklyTotal / $peopleTotal : 0, 2, '.', ' ').' RON', 'class' => ''],
                ['icon' => '📦', 'label' => 'Valoare stoc actual', 'value' => number_format($this->supplyCost, 2, '.', ' ').' RON', 'class' => ''],
            ] as $card)
                <article class="budget-kpi {{ $card['class'] }}">
                    <div class="budget-kpi-icon">{{ $card['icon'] }}</div>
                    <p class="budget-kpi-value">{{ $card['value'] }}</p>
                    <p class="budget-kpi-label mt-2">{{ $card['label'] }}</p>
                </article>
            @endforeach
        </div>

        <x-filament::section heading="Cost pe zi si meniu" description="Compara costul estimat pentru fiecare zi din saptamana selectata.">
            <div class="budget-table-wrap">
                <table class="budget-table">
                    <thead><tr><th>Data</th><th>Persoane</th><th>Cost total</th><th>Cost / persoana</th><th>Status calcul</th></tr></thead>
                    <tbody>
                        @forelse ($this->costs as $cost)
                            <tr>
                                <td class="font-semibold">{{ $cost['date']->format('d.m.Y') }}</td>
                                <td>{{ $cost['people'] }}</td>
                                <td class="font-semibold">{{ number_format($cost['cost'], 2, '.', ' ') }} RON</td>
                                <td>{{ number_format($cost['per_person'], 2, '.', ' ') }} RON</td>
                                <td><span class="budget-status {{ $cost['missing'] ? 'budget-status--missing' : 'budget-status--ok' }}">{{ $cost['missing'] ? 'Preturi incomplete' : 'Calculat' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-6 text-center text-gray-500">Nu exista mese pentru saptamana selectata.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
