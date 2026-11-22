@php
    $showCosts = request()->boolean('costs') && auth()->check();
@endphp
<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Raport saptamana {{ $week->week_number }} - OMS</title>
    <style>
        :root { color: #183c32; background: #f6f6f3; font-family: Arial, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { margin: 0 auto; max-width: 980px; padding: 36px 24px 60px; }
        header { border-bottom: 4px solid #059669; padding-bottom: 20px; }
        .kicker { color: #047857; font-size: 0.78rem; font-weight: 700; letter-spacing: .08em; margin: 0 0 10px; text-transform: uppercase; }
        h1 { font-family: Georgia, serif; font-size: 2.2rem; margin: 0; }
        h2 { font-family: Georgia, serif; font-size: 1.45rem; margin: 0 0 12px; }
        h3 { font-size: 1rem; margin: 18px 0 8px; }
        .dates { margin: 12px 0 0; }
        section { margin-top: 32px; }
        .day-sheet { border-top: 4px solid #059669; page-break-before: always; padding-top: 20px; }
        .day-meta { line-height: 1.55; margin: 0 0 12px; }
        table { background: white; border-collapse: collapse; width: 100%; }
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-wrap > table { min-width: 680px; }
        th { background: #e4efe8; color: #14532d; text-align: left; }
        th, td { border: 1px solid #d6dfd9; padding: 11px 12px; vertical-align: top; }
        .number { text-align: right; white-space: nowrap; }
        .notice { background: #fef3c7; border-left: 4px solid #d97706; margin-top: 16px; padding: 12px 14px; }
        .totals { background: #e4efe8; border-left: 4px solid #047857; line-height: 1.55; margin-top: 12px; padding: 12px 14px; }
        .totals p { margin: 4px 0; }
        .toggle { color: #047857; display: inline-block; font-weight: 700; margin: 18px 0 0 14px; }
        .print { background: #047857; border: 0; color: white; cursor: pointer; font-weight: 700; margin-top: 18px; padding: 10px 16px; }
        @media print { .print, .toggle { display: none; } main { max-width: none; padding: 0; } .day-sheet { break-inside: avoid; } .table-wrap { overflow: visible; } }
    </style>
</head>
<body>
    <main>
        <header>
            <p class="kicker">OMS - raport operational</p>
            <h1>Saptamana {{ $week->week_number }}</h1>
            <p class="dates">{{ $week->periodLabel() }}</p>
            <button class="print" type="button" onclick="window.print()">Printeaza raportul</button>
            @auth
                <a class="toggle" href="{{ request()->fullUrlWithQuery(['costs' => $showCosts ? null : 1]) }}">{{ $showCosts ? 'Ascunde costurile' : 'Arată costurile' }}</a>
            @endauth
        </header>

        @if ($showCosts)
        <section>
            <h2>Rezumat: cost estimat pe zile</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Data</th><th>Fel principal</th><th class="number">Ingrediente</th><th class="number">Ambalaje</th><th class="number">Total</th><th class="number">Cost / portie</th></tr></thead>
                    <tbody>
                        @foreach ($daySheets as $sheet)
                            <tr>
                                <td>{{ $sheet['meal']->meal_date->translatedFormat('D, j M') }}</td>
                                <td>{{ $sheet['meal']->menu?->name ?? '— fără meniu —' }}</td>
                                <td class="number">{{ $sheet['cost']['has_missing_prices'] ? 'De configurat' : number_format($sheet['ingredients_cost'], 2, '.', ' ') . ' RON' }}</td>
                                <td class="number">{{ $sheet['cost']['has_missing_prices'] ? 'De configurat' : number_format($sheet['packaging_cost'], 2, '.', ' ') . ' RON' }}</td>
                                <td class="number">{{ $sheet['cost']['has_missing_prices'] ? 'Partial' : number_format($sheet['cost']['total_cost'], 2, '.', ' ') . ' RON' }}</td>
                                <td class="number">{{ $sheet['cost_per_portion'] === null ? '-' : number_format($sheet['cost_per_portion'], 2, '.', ' ') . ' RON' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($averageCostPerPortion !== null)
                <p class="totals"><strong>Media saptamanii:</strong> {{ number_format($averageCostPerPortion, 2, '.', ' ') }} RON / portie.</p>
            @endif
        </section>
        @endif

        <section>
            <h2>Planificarea meselor</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Data</th><th>Congregatii</th><th>Fel principal</th><th>Ciorba</th><th>Desert / gustare</th><th>Stare / fel gatit</th><th class="number">Portii</th></tr></thead>
                    <tbody>
                        @foreach ($week->dailyMeals as $dailyMeal)
                            <tr>
                                <td>{{ $dailyMeal->meal_date->translatedFormat('D, j M') }}</td>
                                <td>{{ $dailyMeal->congregationNames() ?: '-' }}</td>
                                <td>{{ $dailyMeal->menu?->name ?? '— fără meniu —' }}</td>
                                <td>{{ $dailyMeal->soupMenu?->name ?? '-' }}</td>
                                <td>{{ $dailyMeal->dessertMenu?->name ?? '-' }}</td>
                                <td>{{ $dailyMeal->is_cooked ? 'Gatit' : 'Planificat' }}{{ $dailyMeal->actualMenu && $dailyMeal->actualMenu->id !== $dailyMeal->menu_id ? ': '.$dailyMeal->actualMenu->name : '' }}</td>
                                <td class="number">{{ $dailyMeal->confirmed_people ?? $dailyMeal->estimated_people }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        @foreach ($daySheets as $sheet)
            @php
                $meal = $sheet['meal'];
            @endphp
            <section class="day-sheet">
                <h2>{{ $meal->meal_date->translatedFormat('l, j F Y') }}</h2>
                <p class="day-meta">
                    <strong>Congregatii:</strong> {{ $meal->congregationNames() ?: 'Nealocata' }}<br>
                    <strong>{{ $sheet['portions_label'] }}:</strong> {{ $sheet['portions'] }}
                </p>

                @if ($sheet['components'] === [])
                    <p class="notice">{{ $meal->is_free_day ? 'Zi libera - masa nu se gateste.' : 'Nu exista meniu ales pentru aceasta zi.' }}</p>
                @endif

                @foreach ($sheet['components'] as $component)
                    <h3>{{ $component['label'] }}: {{ $component['menu']->name }}</h3>
                    <p class="day-meta"><strong>Alergeni declarați:</strong> {{ $component['menu']->allergensLabel() }}</p>
                    <table>
                        <thead><tr><th>Ingredient</th><th class="number">Cantitate</th>@if ($showCosts)<th class="number">Cost estimat</th>@endif</tr></thead>
                        <tbody>
                            @foreach ($component['requirements']['ingredients'] as $ingredient)
                                <tr>
                                    <td>{{ $ingredient['name'] }}</td>
                                    <td class="number">{{ $ingredient['needs_configuration'] ? 'De configurat' : rtrim(rtrim(number_format($ingredient['quantity'], 3, '.', ''), '0'), '.') . ' ' . $ingredient['unit'] }}</td>
                                    @if ($showCosts)<td class="number">{{ $ingredient['has_missing_price'] ? 'De configurat' : number_format($ingredient['estimated_cost'], 2, '.', ' ') . ' RON' }}</td>@endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if ($showCosts)
                        @foreach ($component['requirements']['fixed_costs'] as $fixedCost)
                            <p class="day-meta"><strong>{{ $fixedCost['label'] }}:</strong> {{ $fixedCost['has_missing_price'] ? 'De configurat' : number_format($fixedCost['estimated_cost'], 2, '.', ' ') . ' RON' }}</p>
                        @endforeach
                    @endif
                @endforeach

                @if ($sheet['components'] !== [])
                <div class="totals">
                    @if ($sheet['components'] !== [])
                        <p><strong>Ambalaje:</strong> {{ collect($sheet['components'])->sum(fn (array $component): int => (int) $component['requirements']['packaging']['count']) }} caserole.
                            @if ($showCosts)<strong>Cost ambalaje:</strong> {{ number_format(collect($sheet['components'])->sum(fn (array $component): float => (float) $component['requirements']['packaging']['total_cost']), 2, '.', ' ') }} RON.@endif</p>
                    @endif
                    @if (! $showCosts)
                    @elseif ($sheet['cost']['has_missing_prices'])
                        <p><strong>Cost estimat total:</strong> partial - unele ingrediente nu au pret configurat.</p>
                    @else
                        <p><strong>Cost estimat total:</strong> {{ number_format($sheet['cost']['total_cost'], 2, '.', ' ') }} RON.</p>
                        @if ($sheet['cost_per_portion'] !== null)
                            <p><strong>Cost pe portie:</strong> {{ number_format($sheet['cost_per_portion'], 2, '.', ' ') }} RON / portie.</p>
                        @endif
                    @endif
                </div>
                @endif
            </section>
        @endforeach

        <section>
            <h2>Lista de cumpărături</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Ingredient</th><th class="number">Necesar</th><th class="number">De cumpărat</th><th class="number">Rămâne</th>@if ($showCosts)<th class="number">Preț estimat</th><th class="number">Cost estimat</th>@endif</tr></thead>
                    <tbody>
                        @forelse ($shoppingList['ingredients'] as $ingredient)
                            @php
                                $leftover = $ingredient['needs_configuration'] ? null : round($ingredient['to_buy_quantity'] - $ingredient['quantity'], 3);
                            @endphp
                            <tr>
                                <td>{{ $ingredient['name'] }}</td>
                                <td class="number">{{ $ingredient['needs_configuration'] ? 'De configurat' : rtrim(rtrim(number_format($ingredient['quantity'], 3, '.', ''), '0'), '.') . ' ' . $ingredient['unit'] }}</td>
                                <td class="number">{{ $ingredient['needs_configuration'] ? 'De configurat' : \App\Support\PracticalQuantity::format($ingredient['to_buy_quantity'], $ingredient['unit']) }}</td>
                                <td class="number">{{ ($leftover !== null && $leftover > 0.0005) ? \App\Support\PracticalQuantity::format($leftover, $ingredient['unit']) : '—' }}</td>
                                @if ($showCosts)
                                    <td class="number">{{ $ingredient['has_missing_price'] ? 'De configurat' : number_format($ingredient['estimated_unit_cost'], 2, '.', ' ') . ' RON/' . $ingredient['unit'] }}</td>
                                    <td class="number">{{ $ingredient['has_missing_price'] ? '-' : number_format($ingredient['estimated_cost'], 2, '.', ' ') . ' RON' }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $showCosts ? 6 : 4 }}">Nu există meniuri complete pentru această săptămână.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p>Cantitățile de cumpărat sunt rotunjite la pachetele din magazin. Ce rămâne după gătit rămâne la cel care a cumpărat.</p>
            @if ($shoppingList['packaging']['count'] > 0)
                <p><strong>Ambalaje:</strong> {{ $shoppingList['packaging']['count'] }} caserole.@if ($showCosts) <strong>Cost ambalaje:</strong> {{ number_format($shoppingList['packaging']['total_cost'], 2, '.', ' ') }} RON.@endif</p>
            @else
                <p><strong>Ambalaje:</strong> fără ambalaj.</p>
            @endif
            @if ($showCosts)
                @foreach ($shoppingList['fixed_costs'] as $fixedCost)
                    <p><strong>{{ $fixedCost['label'] }}:</strong> {{ $fixedCost['has_missing_price'] ? 'De configurat' : number_format($fixedCost['estimated_cost'], 2, '.', ' ') . ' RON' }}</p>
                @endforeach
                <p><strong>Cost ingrediente configurat:</strong> {{ number_format($shoppingList['totals']['ingredients_cost'], 2, '.', ' ') }} RON. <strong>Total estimat configurat:</strong> {{ number_format($shoppingList['totals']['total_cost'], 2, '.', ' ') }} RON.</p>
                @if ($shoppingList['totals']['has_missing_prices'])
                    <p class="notice">Unele ingrediente nu au preț estimat. Totalul afișat este parțial până la completarea prețurilor din meniuri.</p>
                @endif
            @endif
            @if ($shoppingList['incomplete_meals'] > 0)
                <p class="notice">{{ $shoppingList['incomplete_meals'] === 1 ? '1 zi are meniu fără ingrediente configurate.' : $shoppingList['incomplete_meals'].' zile au meniu fără ingrediente configurate.' }}</p>
            @endif
        </section>
    </main>
</body>
</html>