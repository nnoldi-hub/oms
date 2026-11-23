@if ($weeks->isNotEmpty())
    <div class="oms-week-summary">
        <style>
            .oms-week-summary { --ows-line: #d8e0dc; --ows-green: #047857; --ows-soft: #f4f7f5; color: #16231e; margin: 0 0 18px; max-width: 100%; }
            .oms-week-summary * { box-sizing: border-box; }
            .oms-week-summary__head { align-items: baseline; display: flex; flex-wrap: wrap; gap: 4px 14px; margin-bottom: 8px; }
            .oms-week-summary__title { font-size: 15px; font-weight: 750; margin: 0; }
            .oms-week-summary__hint { color: #66756e; font-size: 12px; margin: 0; }
            .oms-week-summary__scroll { border: 1px solid var(--ows-line); border-radius: 8px; max-width: 100%; overflow-x: auto; }
            .oms-week-summary table { border-collapse: collapse; min-width: 820px; width: 100%; }
            .oms-week-summary th { background: var(--ows-soft); color: #66756e; font-size: 11px; font-weight: 700; letter-spacing: .04em; padding: 8px 12px; text-align: left; text-transform: uppercase; }
            .oms-week-summary td { border-top: 1px solid var(--ows-line); font-size: 13px; padding: 8px 12px; vertical-align: middle; }
            .oms-week-summary tbody tr.is-selected { background: #e7f5ed; }
            .oms-week-summary__week { display: block; font-weight: 700; }
            .oms-week-summary__period { color: #66756e; display: block; font-size: 11px; margin-top: 2px; }
            .oms-week-summary__days { color: var(--ows-green); font-weight: 650; white-space: nowrap; }
            .oms-week-summary__count { color: #66756e; display: block; font-size: 11px; font-weight: 400; }
            .oms-week-summary__cook { font-weight: 600; }
            .oms-week-summary__details { margin-top: 4px; }
            .oms-week-summary__details summary { color: var(--ows-green); cursor: pointer; font-size: 11px; font-weight: 700; }
            .oms-week-summary__details ul { color: #66756e; font-size: 12px; line-height: 1.45; margin: 5px 0 0; padding-left: 16px; }
            .oms-week-summary__details strong { color: #16231e; }
            @media (max-width: 640px) {
                .oms-week-summary__head { align-items: flex-start; flex-direction: column; }
            }
        </style>

        <div class="oms-week-summary__head">
            <p class="oms-week-summary__title">Rezumat program</p>
            <p class="oms-week-summary__hint">Toate săptămânile · zilele se calculează din program</p>
        </div>

        <div class="oms-week-summary__scroll">
            <table>
                <thead>
                    <tr>
                        <th>Săptămâna</th>
                        <th>Se lucrează</th>
                        <th>Gătește</th>
                        <th>Contribuie</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($weeks as $week)
                        <tr class="{{ $selectedWeekId !== null && (int) $week['week_id'] === (int) $selectedWeekId ? 'is-selected' : '' }}">
                            <td>
                                <span class="oms-week-summary__week">Săptămâna {{ $week['week_number'] }}</span>
                                <span class="oms-week-summary__period">{{ $week['period'] }}</span>
                            </td>
                            <td>
                                <span class="oms-week-summary__days">{{ $week['working_days'] }}</span>
                                <span class="oms-week-summary__count">{{ $week['days_count'] }} {{ $week['days_count'] === 1 ? 'zi' : 'zile' }}</span>
                            </td>
                            <td class="oms-week-summary__cook">{{ $week['cooking'] }}</td>
                            <td>
                                <span>{{ $week['contributors'] }}</span>
                                @if ($week['contributors_count'] > 0)
                                    <details class="oms-week-summary__details">
                                        <summary>Pe zile · {{ $week['contributors_count'] }} {{ $week['contributors_count'] === 1 ? 'congregație' : 'congregații' }}</summary>
                                        <ul>
                                            @foreach ($week['contributors_by_day'] as $day)
                                                <li>
                                                    <strong>{{ $day['day'] }}</strong>:
                                                    {{ $day['names'] === [] ? 'Nealocata' : implode(', ', $day['names']) }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
