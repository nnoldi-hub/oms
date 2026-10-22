<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm 9mm 9mm; }

        * { box-sizing: border-box; }

        body {
            color: #16231e;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        table { border-collapse: collapse; width: 100%; }

        .masthead { border-bottom: 3px solid #059669; margin-bottom: 6px; padding-bottom: 5px; }
        .masthead td { vertical-align: bottom; }
        .masthead__period { text-align: right; }
        .kicker { color: #047857; font-size: 7.5pt; font-weight: bold; letter-spacing: .09em; margin: 0 0 2px; text-transform: uppercase; }
        h1 { color: #14532d; font-size: 16pt; margin: 0; }
        .caption { color: #66756e; display: block; font-size: 6.8pt; font-weight: bold; letter-spacing: .05em; text-transform: uppercase; }
        .masthead__period strong { color: #14532d; font-size: 9.5pt; }

        .summary { margin-bottom: 7px; }
        .summary td { background: #f4f7f5; border: 1px solid #d8e0dc; padding: 5px 8px; vertical-align: top; width: 25%; }
        .summary__value { color: #14532d; display: block; font-size: 11pt; font-weight: bold; margin-top: 1px; }
        .summary__note { color: #b45309; display: block; font-size: 7pt; font-weight: bold; }

        .day { margin-bottom: 4px; page-break-inside: avoid; table-layout: fixed; }
        .day td { border: 1px solid #d8e0dc; padding: 4px 6px; vertical-align: top; }
        .day__date strong { color: #14532d; display: block; font-size: 9pt; }
        .day__date span { color: #66756e; display: block; font-size: 7.5pt; }
        .day__badge { color: #b45309; display: block; font-size: 7pt; font-weight: bold; margin-top: 1px; }
        .day__free td { background: #f4f7f5; color: #66756e; }
        .day__free .day__date strong { color: #66756e; }
        .day__congregation { font-weight: bold; }
        .day__main { background: #e7f5ed; }
        .day__main strong { color: #047857; }
        .day__soup { background: #fff5df; }
        .day__soup strong { color: #b45309; }
        .day__dessert { background: #eef2ff; }
        .day__dessert strong { color: #4338ca; }
        .day__number { text-align: right; }
        .day__number .hint { color: #66756e; display: block; font-size: 6.8pt; }
        .day__partial { color: #b45309; display: inline; font-size: 6.8pt; font-weight: bold; margin-left: 3px; }
        .day__roles { background: #fbfdfc; font-size: 7.6pt; }
        .day__roles .role { margin-right: 10px; }
        .role__name { color: #047857; font-weight: bold; }
        .role__phone { color: #66756e; }
        .role__empty { color: #66756e; font-style: italic; }
        .role__missing { color: #be123c; font-weight: bold; }

        .footer { border-top: 2px solid #059669; margin-top: 6px; padding-top: 5px; }
        .footer td { border: 1px solid #d8e0dc; padding: 5px 8px; vertical-align: top; }
        .footer__title { color: #047857; font-size: 7.5pt; font-weight: bold; letter-spacing: .05em; margin: 0 0 3px; text-transform: uppercase; }
        .footer p { margin: 0 0 2px; }
        .footer__generated { text-align: right; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <table class="masthead">
        <tr>
            <td class="masthead__title">
                <p class="kicker">OMS · planificarea meselor pe șantier</p>
                <h1>{{ $report['title'] }}</h1>
            </td>
            <td class="masthead__period">
                <span class="caption">Perioada</span>
                <strong>{{ $report['range'] }}</strong>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>
                <span class="caption">Zile de masă</span>
                <span class="summary__value">{{ $report['totals']['days'] }}</span>
            </td>
            <td>
                <span class="caption">Total porții</span>
                <span class="summary__value">{{ $report['totals']['portions'] }}</span>
            </td>
            <td>
                <span class="caption">{{ $report['totals']['has_missing_prices'] ? 'Cost estimat (parțial)' : 'Cost estimat săptămâna' }}</span>
                <span class="summary__value">{{ number_format($report['totals']['cost'], 2, '.', ' ') }} RON</span>
                @if ($report['totals']['has_missing_prices'])
                    <span class="summary__note">parțial — lipsesc date de cost</span>
                @endif
            </td>
            <td>
                <span class="caption">Cost / porție{{ $report['totals']['has_missing_prices'] ? ' (parțial)' : '' }}</span>
                <span class="summary__value">{{ $report['totals']['cost_per_portion'] === null ? '—' : number_format($report['totals']['cost_per_portion'], 2, '.', ' ').' RON' }}</span>
            </td>
        </tr>
    </table>

    @foreach ($report['days'] as $index => $day)
        @if ($index === 5)
            <div class="page-break"></div>
            <table class="masthead">
                <tr>
                    <td class="masthead__title">
                        <p class="kicker">OMS · planificarea meselor pe șantier</p>
                        <h1>{{ $report['title'] }} — continuare</h1>
                    </td>
                    <td class="masthead__period">
                        <span class="caption">Perioada</span>
                        <strong>{{ $report['range'] }}</strong>
                    </td>
                </tr>
            </table>
        @endif
        <table class="day {{ $day['is_free_day'] ? 'day__free' : '' }}">
            <tr>
                <td class="day__date" style="width: 9%">
                    <strong>{{ $day['weekday'] }}</strong>
                    <span>{{ $day['date'] }}</span>
                    @if ($day['is_free_day'])
                        <span class="day__badge">Zi liberă</span>
                    @endif
                </td>
                <td class="day__congregation" style="width: 13%">{{ $day['congregation'] ?? 'Nealocată' }}</td>
                <td class="day__main" style="width: 16%">@if ($day['main'])<strong>{{ $day['main'] }}</strong>@else—@endif</td>
                <td class="day__soup" style="width: 14%">@if ($day['soup'])<strong>{{ $day['soup'] }}</strong>@else—@endif</td>
                <td class="day__dessert" style="width: 13%">@if ($day['dessert'])<strong>{{ $day['dessert'] }}</strong>@else—@endif</td>
                <td class="day__number" style="width: 9%">
                    @if (! $day['is_free_day'])
                        {{ $day['portions'] }}
                        <span class="hint">{{ $day['portions_are_estimated'] ? 'estimat' : 'confirmat' }}</span>
                    @else
                        —
                    @endif
                </td>
                <td class="day__number" style="width: 14%">
                    @if ($day['is_free_day'])
                        —
                    @else
                        {{ number_format($day['cost'], 2, '.', ' ') }} RON
                        @if ($day['has_missing_prices'])
                            <span class="day__partial">parțial</span>
                        @endif
                    @endif
                </td>
                <td class="day__number" style="width: 12%">
                    @if ($day['cost_per_portion'] === null)
                        —
                    @else
                        {{ number_format($day['cost_per_portion'], 2, '.', ' ') }} RON
                    @endif
                </td>
            </tr>
            <tr>
                <td class="day__roles" colspan="8">
                    @forelse ($day['roles'] as $role)
                        <span class="role">
                            <span class="role__name">{{ $role['label'] }}:</span>
                            @foreach ($role['people'] as $person)
                                {{ $person['name'] }}@if ($person['phone']) <span class="role__phone">({{ $person['phone'] }})</span>@endif{{ $loop->last ? '' : ', ' }}
                            @endforeach
                        </span>
                    @empty
                        <span class="role role__empty">Fără persoane alocate pe roluri.</span>
                    @endforelse
                    @if ($day['uncovered_roles'] !== [])
                        <span class="role role__missing">Roluri neacoperite: {{ implode(', ', $day['uncovered_roles']) }}</span>
                    @endif
                </td>
            </tr>
        </table>
    @endforeach

    <table class="footer">
        <tr>
            @if ($report['contacts'] !== [])
                <td style="width: 34%">
                    <p class="footer__title">Persoane de contact</p>
                    @foreach ($report['contacts'] as $contact)
                        <p>
                            <strong>{{ $contact['name'] }}</strong> · {{ $contact['congregation'] }}
                            @if ($contact['phone']) · {{ $contact['phone'] }}@endif
                            @if ($contact['email']) · {{ $contact['email'] }}@endif
                        </p>
                    @endforeach
                </td>
            @endif
            @if ($report['allergens'] !== [])
                <td style="width: 48%">
                    <p class="footer__title">Alergeni pe zi</p>
                    @foreach ($report['allergens'] as $day)
                        <p><strong>{{ $day['label'] }}:</strong> {{ $day['text'] }}</p>
                    @endforeach
                </td>
            @endif
            <td class="footer__generated" style="width: 18%">
                <p class="footer__title">Generat</p>
                <p>{{ $report['generated_at']->format('d.m.Y H:i') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
