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
        th { background: #e4efe8; color: #14532d; text-align: left; }
        th, td { border: 1px solid #d6dfd9; padding: 11px 12px; vertical-align: top; }
        .number { text-align: right; white-space: nowrap; }
        .notice { background: #fef3c7; border-left: 4px solid #d97706; margin-top: 16px; padding: 12px 14px; }
        .totals { background: #e4efe8; border-left: 4px solid #047857; line-height: 1.55; margin-top: 12px; padding: 12px 14px; }
        .totals p { margin: 4px 0; }
        .print { background: #047857; border: 0; color: white; cursor: pointer; font-weight: 700; margin-top: 18px; padding: 10px 16px; }
        @media print { .print { display: none; } main { max-width: none; padding: 0; } .day-sheet { break-inside: avoid; } }
    </style>
</head>
<body>
    <main>
        <header>
            <p class="kicker">OMS - raport operational</p>
            <h1>Saptamana {{ $week->week_number }}</h1>
            <p class="dates">{{ $week->start_date->translatedFormat('j F Y') }} - {{ $week->start_date->addDays(4)->translatedFormat('j F Y') }}</p>
            <button class="print" type="button" onclick="window.print()">Printeaza raportul</button>
        </header>

        <section>
            <h2>Rezumat: cost estimat pe zile</h2>
            <table>
                <thead><tr><th>Data</th><th>Fel principal</th><th class="number">Ingrediente</th><th class="number">Ambalaje</th><th class="number">Total</th><th class="number">Cost / portie</th></tr></thead>
                <tbody>
                    @foreach ($daySheets as $sheet)
                        <tr>
                            <td>{{ $sheet['meal']->meal_date->translatedFormat('D, j M') }}</td>
                            <td>{{ $sheet['meal']->menu?->name ?? 'Neales' }}</td>
                            <td class="number">{{ $sheet['cost']['has_missing_prices'] ? 'De configurat' : number_format($sheet['ingredients_cost'], 2, '.', ' ') . ' RON' }}</td>
                            <td class="number">{{ $sheet['cost']['has_missing_prices'] ? 'De configurat' : number_format($sheet['packaging_cost'], 2, '.', ' ') . ' RON' }}</td>
                            <td class="number">{{ $sheet['cost']['has_missing_prices'] ? 'Partial' : number_format($sheet['cost']['total_cost'], 2, '.', ' ') . ' RON' }}</td>
                            <td class="number">{{ $sheet['cost_per_portion'] === null ? '-' : number_format($sheet['cost_per_portion'], 2, '.', ' ') . ' RON' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($averageCostPerPortion !== null)
                <p class="totals"><strong>Media saptamanii:</strong> {{ number_format($averageCostPerPortion, 2, '.', ' ') }} RON / portie.</p>
            @endif
        </section>

        <section>
            <h2>Planificarea meselor</h2>
            <table>
                <thead><tr><th>Data</th><th>Congregatie</th><th>Fel principal</th><th>Ciorba</th><th>Desert / gustare</th><th>Stare / fel gatit</th><th class="number">Portii</th></tr></thead>
                <tbody>
                    @foreach ($week->dailyMeals as $dailyMeal)
                        <tr>
                            <td>{{ $dailyMeal->meal_date->translatedFormat('D, j M') }}</td>
                            <td>{{ $dailyMeal->congregation?->name ?? '-' }}</td>
                            <td>{{ $dailyMeal->menu?->name ?? 'Neales' }}</td>
                            <td>{{ $dailyMeal->soupMenu?->name ?? '-' }}</td>
                            <td>{{ $dailyMeal->dessertMenu?->name ?? '-' }}</td>
                            <td>{{ $dailyMeal->is_cooked ? 'Gatit' : 'Planificat' }}{{ $dailyMeal->actualMenu && $dailyMeal->actualMenu->id !== $dailyMeal->menu_id ? ': '.$dailyMeal->actualMenu->name : '' }}</td>
                            <td class="number">{{ $dailyMeal->confirmed_people ?? $dailyMeal->estimated_people }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        @foreach ($daySheets as $sheet)
            @php($meal = $sheet['meal'])
            <section class="day-sheet">
                <h2>{{ $meal->meal_date->translatedFormat('l, j F Y') }}</h2>
                <p class="day-meta">
                    <strong>Congregatie:</strong> {{ $meal->congregation?->name ?? 'Nealocata' }}<br>
                    <strong>{{ $sheet['portions_label'] }}:</strong> {{ $sheet['portions'] }}
                </p>

                @if ($sheet['components'] === [])
                    <p class="notice">{{ $meal->is_free_day ? 'Zi libera - masa nu se gateste.' : 'Nu exista meniu ales pentru aceasta zi.' }}</p>
                @endif

                @foreach ($sheet['components'] as $component)
                    <h3>{{ $component['label'] }}: {{ $component['menu']->name }}</h3>
                    <p class="day-meta"><strong>Alergeni declarati:</strong> {{ is_array($component['menu']->allergens) ? ($component['menu']->allergens === [] ? 'Niciunul' : implode(', ', $component['menu']->allergens)) : 'De confirmat' }}</p>
                    <table>
                        <thead><tr><th>Ingredient</th><th class="number">Cantitate</th><th class="number">Cost estimat</th></tr></thead>
                        <tbody>
                            @foreach ($component['requirements']['ingredients'] as $ingredient)
                                <tr>
                                    <td>{{ $ingredient['name'] }}</td>
                                    <td class="number">{{ rtrim(rtrim(number_format($ingredient['quantity'], 3, '.', ''), '0'), '.') }} {{ $ingredient['unit'] }}</td>
                                    <td class="number">{{ $ingredient['has_missing_price'] ? 'De configurat' : number_format($ingredient['estimated_cost'], 2, '.', ' ') . ' RON' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach

                <div class="totals">
                    @if ($sheet['components'] !== [])
                        <p><strong>Ambalaje:</strong> {{ collect($sheet['components'])->sum(fn (array $component): int => (int) $component['requirements']['packaging']['count']) }} caserole.
                            <strong>Cost ambalaje:</strong> {{ number_format(collect($sheet['components'])->sum(fn (array $component): float => (float) $component['requirements']['packaging']['total_cost']), 2, '.', ' ') }} RON.</p>
                    @endif
                    @if ($sheet['cost']['has_missing_prices'])
                        <p><strong>Cost estimat total:</strong> partial - unele ingrediente nu au pret configurat.</p>
                    @else
                        <p><strong>Cost estimat total:</strong> {{ number_format($sheet['cost']['total_cost'], 2, '.', ' ') }} RON.</p>
                        @if ($sheet['cost_per_portion'] !== null)
                            <p><strong>Cost pe portie:</strong> {{ number_format($sheet['cost_per_portion'], 2, '.', ' ') }} RON / portie.</p>
                        @endif
                    @endif
                </div>
            </section>
        @endforeach

        <section>
            <h2>Lista de cumparaturi bruta</h2>
            <table>
                <thead><tr><th>Ingredient</th><th class="number">Cantitate</th><th class="number">De cumparat</th><th class="number">Pret estimat</th><th class="number">Cost estimat</th></tr></thead>
                <tbody>
                    @forelse ($shoppingList['ingredients'] as $ingredient)
                        <tr>
                            <td>{{ $ingredient['name'] }}</td>
                            <td class="number">{{ rtrim(rtrim(number_format($ingredient['quantity'], 3, '.', ''), '0'), '.') }} {{ $ingredient['unit'] }}</td>
                            <td class="number">{{ \App\Support\PracticalQuantity::format($ingredient['to_buy_quantity'], $ingredient['unit']) }}</td>
                            <td class="number">{{ $ingredient['has_missing_price'] ? 'De configurat' : number_format($ingredient['estimated_unit_cost'], 2, '.', ' ') . ' RON/' . $ingredient['unit'] }}</td>
                            <td class="number">{{ $ingredient['has_missing_price'] ? '-' : number_format($ingredient['estimated_cost'], 2, '.', ' ') . ' RON' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Nu exista meniuri complete pentru aceasta saptamana.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <p><strong>Ambalaje:</strong> {{ $shoppingList['packaging']['count'] }} caserole. <strong>Cost ambalaje:</strong> {{ number_format($shoppingList['packaging']['total_cost'], 2, '.', ' ') }} RON.</p>
            <p><strong>Cost ingrediente configurat:</strong> {{ number_format($shoppingList['totals']['ingredients_cost'], 2, '.', ' ') }} RON. <strong>Total estimat configurat:</strong> {{ number_format($shoppingList['totals']['total_cost'], 2, '.', ' ') }} RON.</p>
            @if ($shoppingList['totals']['has_missing_prices'])
                <p class="notice">Unele ingrediente nu au pret estimat. Totalul afisat este partial pana la completarea preturilor din meniuri.</p>
            @endif
            @if ($shoppingList['incomplete_meals'] > 0)
                <p class="notice">{{ $shoppingList['incomplete_meals'] === 1 ? '1 zi are meniu fara ingrediente sau cost configurat.' : $shoppingList['incomplete_meals'].' zile au meniu fara ingrediente sau cost configurat.' }}</p>
            @endif
        </section>
    </main>
</body>
</html>
