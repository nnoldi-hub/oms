<x-filament-panels::page>
    <style>
        .oms-calendar { --ink: #16231e; --muted: #66756e; --line: #d8e0dc; --surface: #fff; --soft: #f4f7f5; --green: #047857; --green-soft: #e7f5ed; --amber: #b45309; --amber-soft: #fff5df; color: var(--ink); }
        .oms-calendar * { box-sizing: border-box; }
        .oms-calendar__toolbar { align-items: end; border-bottom: 1px solid var(--line); display: flex; gap: 24px; justify-content: space-between; padding-bottom: 20px; }
        .oms-calendar__eyebrow { color: var(--green); font-size: 13px; font-weight: 700; margin: 0 0 6px; text-transform: uppercase; }
        .oms-calendar__title { font-size: 30px; font-weight: 750; line-height: 1.1; margin: 0; }
        .oms-calendar__selector { display: grid; gap: 6px; min-width: 310px; }
        .oms-calendar__selector label, .oms-card__label { color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .oms-calendar select, .oms-card__input, .oms-card__select { background: var(--surface); border: 1px solid #b9c8bf; border-radius: 6px; color: var(--ink); font: inherit; min-height: 42px; padding: 8px 11px; width: 100%; }
        .oms-card__select { min-height: 38px; font-size: 14px; }
        .oms-calendar__weekdays { align-items: center; display: flex; flex-wrap: wrap; gap: 10px; margin: 18px 0 4px; }
        .oms-calendar__weekdays-label { color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .oms-calendar__weekday { align-items: center; background: var(--soft); border: 1px solid var(--line); border-radius: 999px; cursor: pointer; display: inline-flex; font-size: 13px; font-weight: 650; gap: 7px; padding: 7px 13px; }
        .oms-calendar__weekday input { accent-color: var(--green); height: 16px; width: 16px; }
        .oms-calendar__weekday.is-on { background: var(--green-soft); border-color: #91b4a2; color: var(--green); }
        .oms-calendar__summary { align-items: center; display: flex; flex-wrap: wrap; gap: 16px; justify-content: space-between; margin: 22px 0; }
        .oms-calendar__dates { color: var(--muted); font-size: 15px; margin: 0; }
        .oms-calendar__report { background: var(--green); border-radius: 6px; color: #fff; font-size: 14px; font-weight: 700; padding: 11px 15px; text-decoration: none; }
        .oms-calendar__reports { align-items: center; display: flex; flex-wrap: wrap; gap: 8px; }
        .oms-calendar__report--secondary { background: #fff; border: 1px solid #91b4a2; color: #166534; cursor: pointer; }
        .oms-calendar__grid { display: grid; gap: 20px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .oms-card { background: var(--surface); border: 1px solid var(--line); border-radius: 8px; box-shadow: 0 2px 6px rgb(22 35 30 / 8%); display: flex; flex-direction: column; min-height: 350px; overflow: hidden; }
        .oms-card__header { align-items: center; background: var(--soft); border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; padding: 15px 16px; }
        .oms-card__day { font-size: 18px; font-weight: 750; margin: 0; text-transform: capitalize; }
        .oms-card__date { color: var(--muted); font-size: 13px; margin: 3px 0 0; }
        .oms-card__ordinal { background: var(--green-soft); border-radius: 5px; color: var(--green); font-size: 12px; font-weight: 700; padding: 6px 8px; }
        .oms-card__supply-indicators { align-items: center; display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
        .oms-card__supply-indicator { align-items: center; background: var(--surface); border: 1px solid var(--line); border-radius: 999px; cursor: help; display: inline-flex; font-size: 17px; height: 30px; justify-content: center; width: 30px; }
        .oms-card__content { display: grid; gap: 15px; padding: 17px; }
        .oms-card__value { font-size: 15px; font-weight: 650; line-height: 1.45; margin: 4px 0 0; }
        .oms-card__meal { border: 1px solid #b8dbc6; border-radius: 6px; padding: 12px; }
        .oms-card__meal--main { background: var(--green-soft); }
        .oms-card__meal--soup { background: var(--amber-soft); border-color: #f0d29e; }
        .oms-card__meal--dessert { background: #eef2ff; border-color: #bfc9ef; }
        .oms-card__meal--main .oms-card__label { color: var(--green); }
        .oms-card__meal--soup .oms-card__label { color: var(--amber); }
        .oms-card__meal--dessert .oms-card__label { color: #4338ca; }
        .oms-card__meal-row { align-items: center; display: flex; gap: 8px; margin-top: 7px; }
        .oms-card__meal-row .oms-card__select { flex: 1; }
        .oms-card__mini { background: #fff; border: 1px solid #91b4a2; border-radius: 6px; color: #166534; cursor: pointer; font-size: 18px; font-weight: 800; height: 38px; line-height: 1; padding: 0 11px; }
        .oms-card__mini--save { background: var(--green); border-color: var(--green); color: #fff; }
        .oms-card__mini--new { font-size: 14px; padding: 0 10px; }
        .oms-card__warnings { background: var(--amber-soft); border: 1px solid #f0d29e; border-radius: 6px; color: var(--amber); font-size: 13px; line-height: 1.5; margin: 0; padding: 10px 12px; }
        .oms-card__warnings ul { margin: 0; padding-left: 18px; }
        .oms-card__footer { background: var(--soft); border-top: 1px solid var(--line); margin-top: auto; padding: 16px; }
        .oms-card__edit { align-items: center; display: flex; gap: 8px; margin-top: 7px; }
        .oms-card__input { font-size: 20px; font-weight: 750; text-align: center; }
        .oms-card__save { align-items: center; background: var(--green); border: 0; border-radius: 6px; color: #fff; cursor: pointer; display: inline-flex; font-size: 22px; font-weight: 800; height: 42px; justify-content: center; line-height: 1; width: 46px; }
        .oms-card__save:disabled { cursor: wait; opacity: .65; }
        .oms-card__count { color: var(--green); font-size: 32px; font-weight: 750; margin: 5px 0 0; }
        .oms-card__error { color: #be123c; font-size: 13px; margin: 7px 0 0; }
        .oms-card__print { color: var(--green); display: inline-block; font-size: 13px; font-weight: 700; margin-top: 12px; text-decoration: underline; }
        .oms-card__actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .oms-card__action { background: #fff; border: 1px solid #91b4a2; border-radius: 5px; color: #166534; cursor: pointer; font: inherit; font-size: 13px; font-weight: 700; padding: 7px 11px; }
        .oms-card__action:hover { background: var(--green-soft); }
        .oms-card__cooked { align-items: center; background: #fff; border: 1px solid var(--line); border-radius: 6px; display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; padding: 11px 12px; }
        .oms-card__cooked-label { align-items: center; display: flex; font-size: 14px; font-weight: 700; gap: 8px; }
        .oms-card__cooked-label input { accent-color: var(--green); height: 18px; width: 18px; }
        .oms-card__cooked-cost { display: flex; gap: 8px; min-width: 180px; }
        .oms-card__cooked-cost .oms-card__select { min-height: 36px; }
        .oms-card__cooked-cost input { min-height: 36px; text-align: right; }
        .oms-modal { background: rgb(22 35 30 / 55%); display: flex; inset: 0; justify-content: center; padding: 40px 16px; position: fixed; z-index: 60; }
        .oms-modal__panel { background: var(--surface); border-radius: 10px; box-shadow: 0 18px 45px rgb(22 35 30 / 35%); display: flex; flex-direction: column; gap: 14px; max-width: 460px; padding: 22px; width: 100%; }
        .oms-modal__title { font-size: 19px; font-weight: 750; margin: 0; }
        .oms-modal__label { color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .oms-modal__hint { color: var(--muted); font-size: 13px; line-height: 1.5; margin: 0; }
        .oms-modal__row { display: grid; gap: 6px; }
        .oms-modal__radio { align-items: center; display: flex; gap: 8px; font-size: 14px; }
        .oms-modal__buttons { display: flex; gap: 10px; justify-content: flex-end; margin-top: 4px; }
        .oms-modal__confirm { background: var(--green); border: 0; border-radius: 6px; color: #fff; cursor: pointer; font: inherit; font-weight: 700; padding: 9px 15px; }
        .oms-modal__cancel { background: #fff; border: 1px solid var(--line); border-radius: 6px; color: var(--ink); cursor: pointer; font: inherit; font-weight: 700; padding: 9px 15px; }
        .oms-modal__danger { background: #be123c; border: 0; border-radius: 6px; color: #fff; cursor: pointer; font: inherit; font-weight: 700; padding: 9px 15px; }
        .oms-card__budget { border-top: 1px solid var(--line); display: grid; gap: 9px; margin-top: 14px; padding-top: 14px; }
        .oms-card__budget-grid { display: grid; gap: 8px; grid-template-columns: 1fr 1fr auto; }
        .oms-card__budget-input { background: var(--surface); border: 1px solid #b9c8bf; border-radius: 5px; color: var(--ink); min-width: 0; padding: 8px; width: 100%; }
        .oms-card__budget-save { background: #b45309; border: 0; border-radius: 5px; color: #fff; cursor: pointer; font-size: 18px; font-weight: 800; padding: 0 11px; }
        .oms-card__budget-result { font-size: 12px; line-height: 1.45; margin: 0; }
        .oms-card__budget-result strong { color: var(--green); }
        .oms-calendar__empty { border: 1px dashed #aebcb5; border-radius: 8px; color: var(--muted); padding: 32px; text-align: center; }
        .dark .oms-calendar { --ink: #ecf5ef; --muted: #b4c1ba; --line: #3d4d45; --surface: #1e2823; --soft: #26322c; --green-soft: #123c2b; --amber-soft: #3d2c10; }
        .dark .oms-card__meal--dessert { background: #27264d; border-color: #5653a4; }
        .dark .oms-card__meal--dessert .oms-card__label { color: #c7d2fe; }
        .dark .oms-card__cooked, .dark .oms-card__mini { background: #26322c; }
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
                @if (auth()->user()?->isAdmin())
                    <button class="oms-calendar__report oms-calendar__report--secondary" type="button" wire:click="openCreateWeek" style="margin-top:6px;">Saptamana noua</button>
                @endif
            </div>
        </div>

        @if ($this->selectedWeek)
            <div class="oms-calendar__weekdays">
                <span class="oms-calendar__weekdays-label">Zile lucratoare</span>
                @foreach ($this->workingDayDates as $date => $isWorking)
                    <label class="oms-calendar__weekday {{ $isWorking ? 'is-on' : '' }}">
                        <input
                            type="checkbox"
                            @checked($isWorking)
                            @if (auth()->user()?->isAdmin())
                                wire:click="toggleWorkingDay('{{ $date }}')"
                            @else
                                disabled
                            @endif
                        >
                        {{ \Carbon\CarbonImmutable::parse($date)->translatedFormat('D d.m') }}
                    </label>
                @endforeach
            </div>

            <div class="oms-calendar__summary">
                <p class="oms-calendar__dates">{{ $this->selectedWeek->start_date->translatedFormat('j F Y') }} - {{ $this->selectedWeek->start_date->addDays(4)->translatedFormat('j F Y') }}</p>
                <div class="oms-calendar__reports">
                    @if (auth()->user()?->isAdmin())
                        <button class="oms-calendar__report oms-calendar__report--secondary" type="button" wire:click="openSuggestWeek({{ $this->selectedWeek->id }})">Sugereaza meniul</button>
                        <button class="oms-calendar__report oms-calendar__report--secondary" type="button" wire:click="openMoveWeek({{ $this->selectedWeek->id }})">Mută săptămâna</button>
                        <button class="oms-calendar__report oms-calendar__report--secondary" type="button" wire:click="openDeleteWeek({{ $this->selectedWeek->id }})">Șterge săptămâna</button>
                    @endif
                    <a class="oms-calendar__report" href="{{ route('weekly-reports.show', $this->selectedWeek) }}" target="_blank">Raport saptamanal</a>
                    <button
                        class="oms-calendar__report oms-calendar__report--secondary"
                        type="button"
                        x-data
                        x-on:click="navigator.clipboard.writeText(@js($this->whatsappMessages['week'] ?? '')); $wire.call('notifyMessageCopied')"
                    >Copiaza planul saptamanii</button>
                    @foreach ($this->selectedWeek->dailyMeals->pluck('congregation')->filter()->unique('id') as $congregation)
                        <a class="oms-calendar__report oms-calendar__report--secondary" href="{{ route('congregation-weekly-reports.show', [$this->selectedWeek, $congregation]) }}" target="_blank">Fisa {{ $congregation->name }}</a>
                    @endforeach
                </div>
            </div>

            <div class="oms-calendar__grid">
                @foreach ($this->selectedWeek->dailyMeals as $dailyMeal)
                    @php($dailyCost = $this->dailyCosts[$dailyMeal->id])
                    @php($costPerPortion = $dailyMeal->estimated_people > 0 ? $dailyCost['total_cost'] / $dailyMeal->estimated_people : null)
                    @php($maximumPortions = $dailyMeal->maximum_budget !== null && $costPerPortion !== null && $costPerPortion > 0 ? (int) floor((float) $dailyMeal->maximum_budget / $costPerPortion) : null)
                    @php($budgetDifference = $dailyMeal->maximum_budget === null ? null : (float) $dailyMeal->maximum_budget - $dailyCost['total_cost'])
                    @php($contribution = $dailyMeal->contributor_count ? $dailyCost['total_cost'] / $dailyMeal->contributor_count : null)
                    @php($warnings = $this->cardWarnings[$dailyMeal->id] ?? [])
                    <article class="oms-card">
                        <header class="oms-card__header">
                            <div>
                                <p class="oms-card__day">{{ $dailyMeal->meal_date->translatedFormat('l') }}</p>
                                <p class="oms-card__date">{{ $dailyMeal->meal_date->format('d.m.Y') }}</p>
                            </div>
                            <span class="oms-card__ordinal">Ziua {{ $loop->iteration }}</span>
                        </header>
                        @if (!empty($this->supplyIndicators[$dailyMeal->meal_date->toDateString()] ?? []))
                            <div class="oms-card__supply-indicators" aria-label="Resurse de aprovizionare">
                                @foreach ($this->supplyIndicators[$dailyMeal->meal_date->toDateString()] as $indicator)
                                    <span class="oms-card__supply-indicator" title="{{ $indicator['label'] }}" aria-label="{{ $indicator['label'] }}">{{ $indicator['icon'] }}</span>
                                @endforeach
                            </div>
                        @endif
                        <div class="oms-card__content">
                            <div>
                                <p class="oms-card__label">Congregatie</p>
                                <p class="oms-card__value">{{ $dailyMeal->congregation?->name ?? 'Nealocata' }}</p>
                            </div>

                            @php($canPlan = auth()->user()?->isAdmin())
                            @php($mealSections = [
                                ['main', 'Fel principal', 'plannedMenus', $this->menuOptions['main']],
                                ['soup', 'Ciorba', 'plannedSoups', $this->menuOptions['soup']],
                                ['dessert', 'Desert / gustare', 'plannedDesserts', $this->menuOptions['dessert']],
                            ])
                            @foreach ($mealSections as [$type, $label, $property, $options])
                                <div class="oms-card__meal oms-card__meal--{{ $type }}">
                                    <p class="oms-card__label">{{ $label }}</p>
                                    @if ($canPlan)
                                        <div class="oms-card__meal-row">
                                            <select class="oms-card__select" wire:model="{{ $property }}.{{ $dailyMeal->id }}">
                                                <option value="">- neales -</option>
                                                @foreach ($options as $option)
                                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="oms-card__mini oms-card__mini--save" type="button" title="Salveaza" wire:click="savePlannedMenu({{ $dailyMeal->id }}, '{{ $type }}')">✓</button>
                                            <button class="oms-card__mini oms-card__mini--new" type="button" title="Fel nou" wire:click="openNewDish({{ $dailyMeal->id }}, '{{ $type }}')">+ nou</button>
                                        </div>
                                    @else
                                        <p class="oms-card__value">{{ $dailyMeal->{['main' => 'menu', 'soup' => 'soupMenu', 'dessert' => 'dessertMenu'][$type]}?->name ?? '-' }}</p>
                                    @endif
                                </div>
                            @endforeach

                            @if ($warnings !== [])
                                <div class="oms-card__warnings">
                                    <ul>
                                        @foreach ($warnings as $warning)
                                            <li>{{ $warning }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
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
                            <div class="oms-card__budget">
                                <p class="oms-card__label">Buget si contributie</p>
                                @if (auth()->user()?->isAdmin() || auth()->user()?->isCoordinator())
                                    <div class="oms-card__budget-grid">
                                        <input class="oms-card__budget-input" type="number" min="0" step="0.01" wire:model.live.debounce.300ms="maximumBudget.{{ $dailyMeal->id }}" aria-label="Buget maxim in RON">
                                        <input class="oms-card__budget-input" type="number" min="1" step="1" wire:model.live.debounce.300ms="contributorCount.{{ $dailyMeal->id }}" aria-label="Persoane care contribuie">
                                        <button class="oms-card__budget-save" type="button" title="Salveaza bugetul" aria-label="Salveaza bugetul" wire:click="saveBudget({{ $dailyMeal->id }})">✓</button>
                                    </div>
                                @endif
                                @if ($dailyCost['is_partial'])
                                    <p class="oms-card__budget-result"><strong>Cost partial:</strong> {{ number_format($dailyCost['total_cost'], 2, '.', ' ') }} RON pana acum. Completeaza preturile sau introdu costul estimat per portie.</p>
                                @else
                                    <p class="oms-card__budget-result"><strong>Cost estimat:</strong> {{ number_format($dailyCost['total_cost'], 2, '.', ' ') }} RON{{ $costPerPortion === null ? '' : ' (' . number_format($costPerPortion, 2, '.', ' ') . ' RON/portie)' }}.</p>
                                    @if ($maximumPortions !== null)<p class="oms-card__budget-result"><strong>In buget:</strong> aproximativ {{ $maximumPortions }} portii. Diferenta: {{ number_format($budgetDifference, 2, '.', ' ') }} RON.</p>@endif
                                    @if ($contribution !== null)<p class="oms-card__budget-result"><strong>Contributie:</strong> {{ number_format($contribution, 2, '.', ' ') }} RON/persoana.</p>@endif
                                @endif
                            </div>

                            @if (auth()->user()?->isAdmin())
                                <div class="oms-card__cooked">
                                    <label class="oms-card__cooked-label">
                                        <input type="checkbox" wire:model="cookedFlags.{{ $dailyMeal->id }}">
                                        S-a gatit
                                    </label>
                                    <div class="oms-card__cooked-cost">
                                        <select class="oms-card__select" wire:model="actualMenus.{{ $dailyMeal->id }}" title="Felul principal gatit de fapt">
                                            <option value="">Fel real = cel planificat</option>
                                            @foreach ($this->actualMenuOptions['main'] as $option)
                                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                                            @endforeach
                                        </select>
                                        <input class="oms-card__input" type="number" min="0" step="0.01" wire:model="actualCosts.{{ $dailyMeal->id }}" placeholder="Cost real" title="Costul real al zilei (RON)" style="font-size:14px;font-weight:600;">
                                        <button class="oms-card__mini oms-card__mini--save" type="button" title="Salveaza gatitul si costul real" wire:click="markCooked({{ $dailyMeal->id }})">✓</button>
                                    </div>
                                </div>
                            @endif

                            @php($teams = $this->teamsByMeal[$dailyMeal->id] ?? [])
                            @php($people = $this->volunteersByMeal[$dailyMeal->id] ?? [])
                            @php($fund = $this->foodFundByDate[$dailyMeal->meal_date->toDateString()] ?? null)
                            @php($uncovered = $this->uncoveredRoles[$dailyMeal->meal_date->toDateString()] ?? null)

                            @if ($teams !== [])
                                <div>
                                    <p class="oms-card__label">Echipe</p>
                                    <p class="oms-card__value">
                                        @foreach ($teams as $team)
                                            {{ $team->name }}@if ($team->starts_at && $team->ends_at) ({{ substr((string) $team->starts_at, 0, 5) }}-{{ substr((string) $team->ends_at, 0, 5) }})@endif{{ ! $loop->last ? ',' : '' }}
                                        @endforeach
                                    </p>
                                </div>
                            @endif

                            @if ($people !== [])
                                <div>
                                    <p class="oms-card__label">Persoane ({{ count($people) }})</p>
                                    <p class="oms-card__value">
                                        @foreach ($people as $volunteer)
                                            {{ $volunteer->name }} ({{ $volunteer->role }}){{ $volunteer->has_allergies ? ' ⚠' : '' }}{{ ! $loop->last ? ',' : '' }}
                                        @endforeach
                                    </p>
                                </div>
                            @endif

                            @if ($uncovered !== null && $uncovered !== '')
                                <div class="oms-card__warnings">
                                    Roluri neacoperite: {{ $uncovered }}
                                </div>
                            @endif

                            @if ($fund !== null)
                                <div>
                                    <p class="oms-card__label">Fond alimentar (ziua)</p>
                                    <p class="oms-card__budget-result">
                                        Incasari: {{ number_format($fund['income'], 2, '.', ' ') }} RON ·
                                        Cheltuieli: {{ number_format($fund['expense'], 2, '.', ' ') }} RON ·
                                        <strong>Sold: {{ number_format($fund['balance'], 2, '.', ' ') }} RON</strong>
                                    </p>
                                </div>
                            @endif

                            <div class="oms-card__actions">
                                <button class="oms-card__action" type="button" wire:click="openPeople({{ $dailyMeal->id }})">Persoane ({{ count($people) }})</button>
                                <button class="oms-card__action" type="button" wire:click="openTeams({{ $dailyMeal->id }})">Echipe ({{ count($teams) }})</button>
                                <button
                                    class="oms-card__action"
                                    type="button"
                                    x-data
                                    x-on:click="navigator.clipboard.writeText(@js($this->whatsappMessages['days'][$dailyMeal->id] ?? '')); $wire.call('notifyMessageCopied')"
                                >Copiaza mesajul</button>
                                <a
                                    class="oms-card__action"
                                    href="https://wa.me/?text={{ rawurlencode($this->whatsappMessages['days'][$dailyMeal->id] ?? '') }}"
                                    target="_blank"
                                    rel="noopener"
                                >WhatsApp</a>
                            </div>

                            <a class="oms-card__print" href="{{ route('daily-meal-preparation-sheets.show', $dailyMeal) }}" target="_blank">Printeaza ziua</a>
                            @if (auth()->user()?->isAdmin())
                                <div class="oms-card__actions">
                                    <button class="oms-card__action" type="button" wire:click="openMoveDay({{ $dailyMeal->id }})">Mută ziua</button>
                                    <button class="oms-card__action" type="button" wire:click="openFreeDay({{ $dailyMeal->id }})">Zi liberă</button>
                                </div>
                            @endif
                        </footer>
                    </article>
                @endforeach
            </div>

            @php($weekTotal = collect($this->dailyCosts)->sum('total_cost'))
            @php($weekMissing = collect($this->dailyCosts)->where('has_missing_prices', true)->count())
            <div class="oms-card" style="margin-top:20px;min-height:0;">
                <footer class="oms-card__footer">
                    <p class="oms-card__label">Sumar financiar saptamanal</p>
                    <p class="oms-card__budget-result">
                        <strong>Cost estimat total:</strong> {{ number_format($weekTotal, 2, '.', ' ') }} RON
                        @if ($weekMissing > 0)
                            · {{ $weekMissing }} {{ $weekMissing === 1 ? 'zi are' : 'zile au' }} preturi lipsa (cost partial)
                        @endif
                    </p>
                </footer>
            </div>
        @else
            <div class="oms-calendar__empty">Nu exista saptamani disponibile pentru acest cont.</div>
        @endif

        @if ($suggestWeekId !== null)
            <div class="oms-modal">
                <div class="oms-modal__panel">
                    <h2 class="oms-modal__title">Sugereaza meniul</h2>
                    <p class="oms-modal__hint">Completeaza automat doar zilele goale din saptamanii selectata, folosind rotatia existenta. Zilele cu meniu deja ales nu sunt modificate.</p>
                    <div class="oms-modal__buttons">
                        <button class="oms-modal__cancel" type="button" wire:click="closeSchedulingAction">Renunta</button>
                        <button class="oms-modal__confirm" type="button" wire:click="suggestMenus">Sugereaza</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($newDishMealId !== null)
            <div class="oms-modal">
                <div class="oms-modal__panel">
                    <h2 class="oms-modal__title">Fel nou</h2>
                    <p class="oms-modal__hint">Creeaza un fel care se adauga in catalog si se selecteaza automat pe aceasta zi. Tipul este preluat din locul de unde ai pornit.</p>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="new-dish-name">Denumire</label>
                        <input class="oms-card__input" id="new-dish-name" type="text" wire:model="newDishName" placeholder="ex. Sarmale">
                        @error('newDishName')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <p class="oms-modal__hint">Tip: {{ ['main' => 'fel principal', 'soup' => 'ciorba', 'dessert' => 'desert / gustare'][$newDishType] ?? $newDishType }}</p>
                    <div class="oms-modal__buttons">
                        <button class="oms-modal__cancel" type="button" wire:click="closeNewDish">Renunta</button>
                        <button class="oms-modal__confirm" type="button" wire:click="createDish">Creeaza fel</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($pendingWorkingDayMealId !== null)
            <div class="oms-modal">
                <div class="oms-modal__panel">
                    <h2 class="oms-modal__title">Scoate ziua din program</h2>
                    <p class="oms-modal__hint">Ziua {{ $pendingWorkingDayDate ? \Carbon\CarbonImmutable::parse($pendingWorkingDayDate)->format('d.m.Y') : '' }} are deja meniu, portii sau plan de aprovizionare. Daca o scoti, se sterg masa si planurile asociate. Cheltuielile din fondul alimentar raman neschimbate.</p>
                    <div class="oms-modal__buttons">
                        <button class="oms-modal__cancel" type="button" wire:click="closeSchedulingAction">Renunta</button>
                        <button class="oms-modal__danger" type="button" wire:click="confirmDeleteWorkingDay">Sterge ziua</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($moveDayMealId !== null)
            <div class="oms-modal">
                <div class="oms-modal__panel">
                    <h2 class="oms-modal__title">Mută ziua</h2>
                    <p class="oms-modal__hint">Alege data noua. Retetele zilei raman neschimbate; vei primi doar avertizari daca rotatia iese din ritm.</p>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="move-day-date">Data noua</label>
                        <input class="oms-card__input" id="move-day-date" type="date" wire:model="moveDayDate">
                        @error('moveDayDate')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <div class="oms-modal__buttons">
                        <button class="oms-modal__cancel" type="button" wire:click="closeSchedulingAction">Renunta</button>
                        <button class="oms-modal__confirm" type="button" wire:click="moveDay">Mută ziua</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($moveWeekId !== null)
            <div class="oms-modal">
                <div class="oms-modal__panel">
                    <h2 class="oms-modal__title">Mută săptămâna</h2>
                    <p class="oms-modal__hint">Muta toate zilele saptamanii cu numarul de zile ales. Meniurile si bugetele raman pe zilele respective.</p>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="move-week-days">Numar de zile</label>
                        <input class="oms-card__input" id="move-week-days" type="number" min="-30" max="30" wire:model="moveWeekDays">
                        @error('moveWeekDays')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <div class="oms-modal__buttons">
                        <button class="oms-modal__cancel" type="button" wire:click="closeSchedulingAction">Renunta</button>
                        <button class="oms-modal__confirm" type="button" wire:click="moveWeek">Mută săptămâna</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($freeDayMealId !== null)
            <div class="oms-modal">
                <div class="oms-modal__panel">
                    <h2 class="oms-modal__title">Zi liberă</h2>
                    <p class="oms-modal__hint">Ziua este scoasa din rotatie (retetele se golesc). Alege daca muti si restul programului.</p>
                    <label class="oms-modal__radio">
                        <input type="radio" value="0" wire:model="freeDayShiftRest">
                        Lasă restul neschimbat
                    </label>
                    <label class="oms-modal__radio">
                        <input type="radio" value="1" wire:model="freeDayShiftRest">
                        Mută restul programului cu o zi
                    </label>
                    <div class="oms-modal__buttons">
                        <button class="oms-modal__cancel" type="button" wire:click="closeSchedulingAction">Renunta</button>
                        <button class="oms-modal__confirm" type="button" wire:click="markFreeDay">Marcheaza ziua libera</button>
                    </div>
                </div>
            </div>
        @endif
        @if ($peopleMealId !== null)
            <div class="oms-modal">
                <div class="oms-modal__panel">
                    <h2 class="oms-modal__title">Persoane si roluri</h2>
                    <p class="oms-modal__hint">Adauga persoanele zilei cu responsabilitatea lor. Persoanele cu alergii sunt marcate cu ⚠.</p>

                    <div>
                        @foreach ($this->volunteersByMeal[$peopleMealId] ?? [] as $volunteer)
                            <div class="oms-modal__radio" style="justify-content:space-between;">
                                <span>{{ $volunteer->name }} ({{ $volunteer->role }}){{ $volunteer->has_allergies ? ' ⚠ '.$volunteer->allergy_details : '' }}</span>
                                <span>
                                    <button class="oms-card__action" type="button" wire:click="editVolunteer({{ $volunteer->id }})">Editeaza</button>
                                    <button class="oms-card__action" type="button" wire:click="deleteVolunteer({{ $volunteer->id }})">Sterge</button>
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="volunteer-name">Nume</label>
                        <input class="oms-card__input" id="volunteer-name" type="text" wire:model="volunteerName" style="font-size:15px;font-weight:600;">
                        @error('volunteerName')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="volunteer-role">Responsabilitate</label>
                        <select class="oms-card__select" id="volunteer-role" wire:model="volunteerRole">
                            <option value="">- alege rol -</option>
                            @foreach ($this->roleOptions as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                            @if ($volunteerRole !== '' && ! in_array($volunteerRole, $this->roleOptions, true))
                                <option value="{{ $volunteerRole }}">{{ $volunteerRole }}</option>
                            @endif
                        </select>
                        @error('volunteerRole')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="volunteer-phone">Telefon</label>
                        <input class="oms-card__input" id="volunteer-phone" type="tel" wire:model="volunteerPhone" style="font-size:15px;font-weight:600;">
                        @error('volunteerPhone')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <label class="oms-modal__radio">
                        <input type="checkbox" wire:model.live="volunteerHasAllergies">
                        Are alergii
                    </label>
                    @if ($volunteerHasAllergies)
                        <div class="oms-modal__row">
                            <label class="oms-modal__label" for="volunteer-allergies">Detalii alergii</label>
                            <input class="oms-card__input" id="volunteer-allergies" type="text" wire:model="volunteerAllergyDetails" style="font-size:15px;font-weight:600;">
                            @error('volunteerAllergyDetails')<p class="oms-card__error">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div class="oms-modal__buttons">
                        <button class="oms-modal__cancel" type="button" wire:click="closePeople">Inchide</button>
                        @if ($editingVolunteerId !== null)
                            <button class="oms-modal__cancel" type="button" wire:click="cancelVolunteerEdit">Anuleaza editarea</button>
                        @endif
                        <button class="oms-modal__confirm" type="button" wire:click="saveVolunteer">{{ $editingVolunteerId !== null ? 'Salveaza modificarile' : 'Adauga persoana' }}</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($teamsMealId !== null)
            <div class="oms-modal">
                <div class="oms-modal__panel">
                    <h2 class="oms-modal__title">Echipe de lucru</h2>
                    <p class="oms-modal__hint">Echipele zilei cu interval orar, locatie si responsabil de aprovizionare.</p>

                    <div>
                        @foreach ($this->teamsByMeal[$teamsMealId] ?? [] as $team)
                            <div class="oms-modal__radio" style="justify-content:space-between;">
                                <span>{{ $team->name }}@if ($team->starts_at && $team->ends_at) ({{ substr((string) $team->starts_at, 0, 5) }}-{{ substr((string) $team->ends_at, 0, 5) }})@endif{{ $team->location ? ' · '.$team->location : '' }}</span>
                                <span>
                                    <button class="oms-card__action" type="button" wire:click="editTeam({{ $team->id }})">Editeaza</button>
                                    <button class="oms-card__action" type="button" wire:click="deleteTeam({{ $team->id }})">Sterge</button>
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="team-name">Nume echipa</label>
                        <input class="oms-card__input" id="team-name" type="text" wire:model="teamName" style="font-size:15px;font-weight:600;">
                        @error('teamName')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="team-members">Numar membri</label>
                        <input class="oms-card__input" id="team-members" type="number" min="0" wire:model="teamMemberCount" style="font-size:15px;font-weight:600;">
                        @error('teamMemberCount')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="team-start">De la ora</label>
                        <input class="oms-card__input" id="team-start" type="time" wire:model="teamStartsAt" style="font-size:15px;font-weight:600;">
                        @error('teamStartsAt')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="team-end">Pana la ora</label>
                        <input class="oms-card__input" id="team-end" type="time" wire:model="teamEndsAt" style="font-size:15px;font-weight:600;">
                        @error('teamEndsAt')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="team-location">Locatie</label>
                        <input class="oms-card__input" id="team-location" type="text" wire:model="teamLocation" style="font-size:15px;font-weight:600;">
                        @error('teamLocation')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="team-supply">Responsabil aprovizionare</label>
                        <input class="oms-card__input" id="team-supply" type="text" wire:model="teamSupplyResponsible" style="font-size:15px;font-weight:600;">
                        @error('teamSupplyResponsible')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="oms-modal__buttons">
                        <button class="oms-modal__cancel" type="button" wire:click="closeTeams">Inchide</button>
                        @if ($editingTeamId !== null)
                            <button class="oms-modal__cancel" type="button" wire:click="cancelTeamEdit">Anuleaza editarea</button>
                        @endif
                        <button class="oms-modal__confirm" type="button" wire:click="saveTeam">{{ $editingTeamId !== null ? 'Salveaza modificarile' : 'Adauga echipa' }}</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($showCreateWeek)
            <div class="oms-modal">
                <div class="oms-modal__panel">
                    <h2 class="oms-modal__title">Saptamana noua</h2>
                    <p class="oms-modal__hint">Creeaza o saptamana noua. Dupa creare, poti adauga zile prin bifarea zilelor lucratoare.</p>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="new-week-number">Numar saptamana</label>
                        <input class="oms-card__input" id="new-week-number" type="number" min="1" max="520" wire:model="newWeekNumber" style="font-size:15px;font-weight:600;">
                        @error('newWeekNumber')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="new-week-start">Data inceput (luni)</label>
                        <input class="oms-card__input" id="new-week-start" type="date" wire:model="newWeekStartDate" style="font-size:15px;font-weight:600;">
                        @error('newWeekStartDate')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <div class="oms-modal__row">
                        <label class="oms-modal__label" for="new-week-congregation">Congregatie principala</label>
                        <select class="oms-card__select" id="new-week-congregation" wire:model="newWeekCongregationId">
                            <option value="">- alege -</option>
                            @foreach ($this->congregationOptions as $congregation)
                                <option value="{{ $congregation->id }}">{{ $congregation->name }}</option>
                            @endforeach
                        </select>
                        @error('newWeekCongregationId')<p class="oms-card__error">{{ $message }}</p>@enderror
                    </div>
                    <div class="oms-modal__buttons">
                        <button class="oms-modal__cancel" type="button" wire:click="closeCreateWeek">Renunta</button>
                        <button class="oms-modal__confirm" type="button" wire:click="createWeek">Creeaza saptamana</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($pendingDeleteWeekId !== null)
            <div class="oms-modal">
                <div class="oms-modal__panel">
                    <h2 class="oms-modal__title">Șterge săptămâna</h2>
                    <p class="oms-modal__hint">Se sterg saptamana si toate zilele ei, cu planurile de aprovizionare si echipele asociate. Cheltuielile din fondul alimentar raman neschimbate. Actiunea nu poate fi anulata.</p>
                    <div class="oms-modal__buttons">
                        <button class="oms-modal__cancel" type="button" wire:click="closeDeleteWeek">Renunta</button>
                        <button class="oms-modal__danger" type="button" wire:click="confirmDeleteWeek">Șterge săptămâna</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
