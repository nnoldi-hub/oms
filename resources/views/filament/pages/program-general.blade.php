<x-filament-panels::page>
    <style>
        .program-page { --program-line: rgb(148 163 184 / .2); --program-muted: rgb(100 116 139); }
        .program-hero { align-items: end; background: linear-gradient(135deg, rgb(5 150 105 / .14), rgb(15 23 42 / .06)); border: 1px solid rgb(5 150 105 / .25); border-radius: 18px; display: flex; gap: 20px; justify-content: space-between; padding: 28px 30px; }
        .program-hero h1 { font-size: clamp(1.8rem, 3vw, 2.5rem); letter-spacing: -.04em; }
        .program-filter { align-items: end; display: grid; gap: 14px; grid-template-columns: minmax(0, 1fr) auto; }
        .program-filter label { color: var(--program-muted); font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .program-select { background: white; border: 1px solid var(--program-line); border-radius: 10px; display: block; font-size: .9rem; margin-top: 7px; min-height: 42px; padding: 0 38px 0 12px; width: 100%; }
        .program-table-wrap { border: 1px solid var(--program-line); border-radius: 14px; overflow-x: auto; }
        .program-table { border-collapse: collapse; min-width: 820px; width: 100%; }
        .program-table th { background: rgb(100 116 139 / .12); color: var(--program-muted); font-size: .7rem; letter-spacing: .05em; padding: 14px 16px; text-align: left; text-transform: uppercase; }
        .program-table td { border-top: 1px solid var(--program-line); padding: 13px 16px; vertical-align: middle; }
        .program-table tbody tr:nth-child(even) { background: rgb(100 116 139 / .045); }
        .program-badge { border-radius: 999px; display: inline-flex; font-size: .72rem; font-weight: 700; padding: 5px 9px; }
        .program-badge--free { background: rgb(100 116 139 / .12); color: var(--program-muted); }
        .program-badge--cooked { background: rgb(16 185 129 / .13); color: rgb(4 120 87); }
        @media (max-width: 600px) { .program-hero { align-items: start; flex-direction: column; padding: 22px; } .program-filter { grid-template-columns: 1fr; } }
    </style>

    <div class="program-page space-y-6">
        <header class="program-hero">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-success-600">🗓️ Vedere de ansamblu</p>
                <h1 class="mt-2 font-bold">Program general</h1>
                <p class="mt-2 text-sm text-gray-500">
                    Programul saptamanal al tuturor congregatiilor, doar pentru citire.
                    Editarea se face din calendarul saptamanal, pe zilele congregatiei tale.
                </p>
            </div>
            <div class="hidden text-right text-sm text-gray-500 md:block">
                <span class="block font-semibold text-gray-700 dark:text-gray-200">Saptamana selectata</span>
                {{ $this->selectedWeek?->start_date?->format('d.m.Y') ?? 'Neselectata' }}
            </div>
        </header>

        <x-filament::section heading="Saptamana">
            <div class="program-filter">
                <div>
                    <label for="program-week">Saptamana</label>
                    <select id="program-week" wire:model.live="weekId" class="program-select">
                        @foreach ($this->weeks as $week)
                            <option value="{{ $week->id }}">Saptamana {{ $week->week_number }} · {{ $week->start_date->format('d.m.Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="text-sm text-gray-500">
                    {{ $this->selectedWeek?->dailyMeals->count() ?? 0 }} zile programate
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Zilele programate" description="Toate congregatiile: data, meniurile si starea de gatire. Fara costuri, portii, persoane sau contributii.">
            <div class="program-table-wrap">
                <table class="program-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Congregatia</th>
                            <th>Fel principal</th>
                            <th>Ciorba</th>
                            <th>Desert</th>
                            <th>S-a gatit</th>
                            <th>Felul real</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->programDays as $day)
                            <tr>
                                <td class="font-semibold">{{ $day['date'] }}</td>
                                <td>{{ $day['congregation'] }}</td>
                                <td>
                                    @if ($day['is_free_day'])
                                        <span class="program-badge program-badge--free">Zi libera</span>
                                    @else
                                        {{ $day['main'] ?? '-' }}
                                    @endif
                                </td>
                                <td>{{ $day['soup'] ?? '-' }}</td>
                                <td>{{ $day['dessert'] ?? '-' }}</td>
                                <td>
                                    @if ($day['is_cooked'])
                                        <span class="program-badge program-badge--cooked">S-a gatit</span>
                                    @else
                                        {{ '-' }}
                                    @endif
                                </td>
                                <td>{{ $day['actual'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-6 text-center text-gray-500">Nu exista zile programate pentru saptamana selectata.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
