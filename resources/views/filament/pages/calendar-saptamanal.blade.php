<x-filament-panels::page>
    <style>
        .oms-calendar { --ink: #16231e; --muted: #66756e; --line: #d8e0dc; --surface: #fff; --soft: #f4f7f5; --green: #047857; --green-soft: #e7f5ed; --amber: #b45309; --amber-soft: #fff5df; color: var(--ink); }
        .oms-calendar * { box-sizing: border-box; }
        .oms-calendar__toolbar { align-items: end; border-bottom: 1px solid var(--line); display: flex; gap: 24px; justify-content: space-between; padding-bottom: 20px; }
        .oms-calendar__eyebrow { color: var(--green); font-size: 13px; font-weight: 700; margin: 0 0 6px; text-transform: uppercase; }
        .oms-calendar__title { font-size: 30px; font-weight: 750; line-height: 1.1; margin: 0; }
        .oms-calendar__selector { display: grid; gap: 6px; min-width: 310px; }
        .oms-calendar__selector label, .oms-card__label { color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .oms-calendar select, .oms-card__input { background: var(--surface); border: 1px solid #b9c8bf; border-radius: 6px; color: var(--ink); font: inherit; min-height: 42px; padding: 8px 11px; width: 100%; }
        .oms-calendar__summary { align-items: center; display: flex; flex-wrap: wrap; gap: 16px; justify-content: space-between; margin: 22px 0; }
        .oms-calendar__dates { color: var(--muted); font-size: 15px; margin: 0; }
        .oms-calendar__report { background: var(--green); border-radius: 6px; color: #fff; font-size: 14px; font-weight: 700; padding: 11px 15px; text-decoration: none; }
        .oms-calendar__reports { align-items: center; display: flex; flex-wrap: wrap; gap: 8px; }
        .oms-calendar__report--secondary { background: #fff; border: 1px solid #91b4a2; color: #166534; }
        .oms-calendar__grid { display: grid; gap: 20px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .oms-card { background: var(--surface); border: 1px solid var(--line); border-radius: 8px; box-shadow: 0 2px 6px rgb(22 35 30 / 8%); display: flex; flex-direction: column; min-height: 350px; overflow: hidden; }
        .oms-card__header { align-items: center; background: var(--soft); border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; padding: 15px 16px; }
        .oms-card__day { font-size: 18px; font-weight: 750; margin: 0; text-transform: capitalize; }
        .oms-card__date { color: var(--muted); font-size: 13px; margin: 3px 0 0; }
        .oms-card__ordinal { background: var(--green-soft); border-radius: 5px; color: var(--green); font-size: 12px; font-weight: 700; padding: 6px 8px; }
        .oms-card__content { display: grid; gap: 15px; padding: 17px; }
        .oms-card__value { font-size: 15px; font-weight: 650; line-height: 1.45; margin: 4px 0 0; }
        .oms-card__meal { border: 1px solid #b8dbc6; border-radius: 6px; padding: 12px; }
        .oms-card__meal--main { background: var(--green-soft); }
        .oms-card__meal--soup { background: var(--amber-soft); border-color: #f0d29e; }
        .oms-card__meal--main .oms-card__label { color: var(--green); }
        .oms-card__meal--soup .oms-card__label { color: var(--amber); }
        .oms-card__footer { background: var(--soft); border-top: 1px solid var(--line); margin-top: auto; padding: 16px; }
        .oms-card__edit { align-items: center; display: flex; gap: 8px; margin-top: 7px; }
        .oms-card__input { font-size: 20px; font-weight: 750; text-align: center; }
        .oms-card__save { align-items: center; background: var(--green); border: 0; border-radius: 6px; color: #fff; cursor: pointer; display: inline-flex; font-size: 22px; font-weight: 800; height: 42px; justify-content: center; line-height: 1; width: 46px; }
        .oms-card__save:disabled { cursor: wait; opacity: .65; }
        .oms-card__count { color: var(--green); font-size: 32px; font-weight: 750; margin: 5px 0 0; }
        .oms-card__error { color: #be123c; font-size: 13px; margin: 7px 0 0; }
        .oms-card__print { color: var(--green); display: inline-block; font-size: 13px; font-weight: 700; margin-top: 12px; text-decoration: underline; }
        .oms-calendar__empty { border: 1px dashed #aebcb5; border-radius: 8px; color: var(--muted); padding: 32px; text-align: center; }
        .dark .oms-calendar { --ink: #ecf5ef; --muted: #b4c1ba; --line: #3d4d45; --surface: #1e2823; --soft: #26322c; --green-soft: #123c2b; --amber-soft: #3d2c10; }
        @media (max-width: 1100px) { .oms-calendar__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 700px) { .oms-calendar__toolbar { align-items: stretch; flex-direction: column; } .oms-calendar__selector { min-width: 0; } .oms-calendar__grid { grid-template-columns: 1fr; } .oms-calendar__title { font-size: 26px; } }
    </style>

    <div class="oms-calendar">
        <div class="oms-calendar__toolbar">
            <div>
                <p class="oms-calendar__eyebrow">Planificare mese</p>
                <h1 class="oms-calendar__title">Calendar saptamanal</h1>
            </div>
            <div class="oms-calendar__selector">
                <label for="calendar-week">Alege saptamana</label>
                <select id="calendar-week" wire:model.live="weekId">
                    @foreach ($this->weeks as $week)
                        <option value="{{ $week->id }}">Saptamana {{ $week->week_number }}: {{ $week->start_date->format('d.m') }} - {{ $week->start_date->addDays(4)->format('d.m.Y') }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($this->selectedWeek)
            <div class="oms-calendar__summary">
                <p class="oms-calendar__dates">{{ $this->selectedWeek->start_date->translatedFormat('j F Y') }} - {{ $this->selectedWeek->start_date->addDays(4)->translatedFormat('j F Y') }}</p>
                <div class="oms-calendar__reports">
                    <a class="oms-calendar__report" href="{{ route('weekly-reports.show', $this->selectedWeek) }}" target="_blank">Raport saptamanal</a>
                    @foreach ($this->selectedWeek->dailyMeals->pluck('congregation')->filter()->unique('id') as $congregation)
                        <a class="oms-calendar__report oms-calendar__report--secondary" href="{{ route('congregation-weekly-reports.show', [$this->selectedWeek, $congregation]) }}" target="_blank">Fisa {{ $congregation->name }}</a>
                    @endforeach
                </div>
            </div>

            <div class="oms-calendar__grid">
                @foreach ($this->selectedWeek->dailyMeals as $dailyMeal)
                    <article class="oms-card">
                        <header class="oms-card__header">
                            <div>
                                <p class="oms-card__day">{{ $dailyMeal->meal_date->translatedFormat('l') }}</p>
                                <p class="oms-card__date">{{ $dailyMeal->meal_date->format('d.m.Y') }}</p>
                            </div>
                            <span class="oms-card__ordinal">Ziua {{ $loop->iteration }}</span>
                        </header>
                        <div class="oms-card__content">
                            <div>
                                <p class="oms-card__label">Congregatie</p>
                                <p class="oms-card__value">{{ $dailyMeal->congregation?->name ?? 'Nealocata' }}</p>
                            </div>
                            <div class="oms-card__meal oms-card__meal--main">
                                <p class="oms-card__label">Fel principal</p>
                                <p class="oms-card__value">{{ $dailyMeal->menu?->name ?? 'Neales' }}</p>
                            </div>
                            <div class="oms-card__meal oms-card__meal--soup">
                                <p class="oms-card__label">Ciorba suplimentara</p>
                                <p class="oms-card__value">{{ $dailyMeal->soupMenu?->name ?? '-' }}</p>
                            </div>
                        </div>
                        <footer class="oms-card__footer">
                            <p class="oms-card__label">Portii estimate</p>
                            @if (auth()->user()?->isAdmin() || auth()->user()?->isConstructionTeam())
                                <div class="oms-card__edit">
                                    <input class="oms-card__input" type="number" min="0" max="5000" wire:model.live.debounce.300ms="estimatedPeople.{{ $dailyMeal->id }}" aria-label="Portii estimate pentru {{ $dailyMeal->meal_date->format('d.m.Y') }}">
                                    <button class="oms-card__save" type="button" title="Salveaza portiile" aria-label="Salveaza portiile" wire:click="saveEstimatedPeople({{ $dailyMeal->id }})" wire:loading.attr="disabled" wire:target="saveEstimatedPeople({{ $dailyMeal->id }})">✓</button>
                                </div>
                                @error("estimatedPeople.{$dailyMeal->id}")
                                    <p class="oms-card__error">{{ $message }}</p>
                                @enderror
                            @else
                                <p class="oms-card__count">{{ $dailyMeal->estimated_people }}</p>
                            @endif
                            <a class="oms-card__print" href="{{ route('daily-meal-preparation-sheets.show', $dailyMeal) }}" target="_blank">Printeaza ziua</a>
                        </footer>
                    </article>
                @endforeach
            </div>
        @else
            <div class="oms-calendar__empty">Nu exista saptamani disponibile pentru acest cont.</div>
        @endif
    </div>
</x-filament-panels::page>
