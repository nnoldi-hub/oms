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
        .dates { margin: 12px 0 0; }
        section { margin-top: 32px; }
        table { background: white; border-collapse: collapse; width: 100%; }
        th { background: #e4efe8; color: #14532d; text-align: left; }
        th, td { border: 1px solid #d6dfd9; padding: 11px 12px; vertical-align: top; }
        .number { text-align: right; white-space: nowrap; }
        .notice { background: #fef3c7; border-left: 4px solid #d97706; margin-top: 16px; padding: 12px 14px; }
        .danger { background: #fff1f2; border-left: 4px solid #e11d48; padding: 12px 14px; }
        .print { background: #047857; border: 0; color: white; cursor: pointer; font-weight: 700; margin-top: 18px; padding: 10px 16px; }
        @media print { .print { display: none; } main { max-width: none; padding: 0; } }
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
            <h2>Lista de cumparaturi bruta</h2>
            <table>
                <thead><tr><th>Ingredient</th><th class="number">Cantitate</th><th class="number">Pret estimat</th><th class="number">Cost estimat</th></tr></thead>
                <tbody>
                    @forelse ($shoppingList['ingredients'] as $ingredient)
                        <tr>
                            <td>{{ $ingredient['name'] }}</td>
                            <td class="number">{{ rtrim(rtrim(number_format($ingredient['quantity'], 3, '.', ''), '0'), '.') }} {{ $ingredient['unit'] }}</td>
                            <td class="number">{{ $ingredient['has_missing_price'] ? 'De configurat' : number_format($ingredient['estimated_unit_cost'], 2, '.', ' ') . ' RON/' . $ingredient['unit'] }}</td>
                            <td class="number">{{ $ingredient['has_missing_price'] ? '-' : number_format($ingredient['estimated_cost'], 2, '.', ' ') . ' RON' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Nu exista meniuri complete pentru aceasta saptamana.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <p><strong>Ambalaje:</strong> {{ $shoppingList['packaging']['count'] }} caserole. <strong>Cost ambalaje:</strong> {{ number_format($shoppingList['packaging']['total_cost'], 2, '.', ' ') }} RON.</p>
            <p><strong>Cost ingrediente configurat:</strong> {{ number_format($shoppingList['totals']['ingredients_cost'], 2, '.', ' ') }} RON. <strong>Total estimat configurat:</strong> {{ number_format($shoppingList['totals']['total_cost'], 2, '.', ' ') }} RON.</p>
            @if ($shoppingList['totals']['has_missing_prices'])
                <p class="notice">Unele ingrediente nu au pret estimat. Totalul afisat este partial pana la completarea preturilor din meniuri.</p>
            @endif
            @if ($shoppingList['incomplete_meals'] > 0)
                <p class="notice">{{ $shoppingList['incomplete_meals'] }} zile nu au meniu ales si nu sunt incluse in total.</p>
            @endif
        </section>

        <section>
            <h2>Cost estimat pe zile</h2>
            <table>
                <thead><tr><th>Data</th><th>Fel principal</th><th class="number">Ingrediente</th><th class="number">Ambalaje</th><th class="number">Total</th></tr></thead>
                <tbody>
                    @foreach ($week->dailyMeals as $dailyMeal)
                        @php($dailyCost = collect($shoppingList['daily_costs'])->firstWhere('daily_meal_id', $dailyMeal->id))
                        <tr>
                            <td>{{ $dailyMeal->meal_date->translatedFormat('D, j M') }}</td>
                            <td>{{ $dailyMeal->menu?->name ?? 'Neales' }}</td>
                            <td class="number">{{ $dailyCost && ! $dailyCost['has_missing_prices'] ? number_format($dailyCost['ingredients_cost'], 2, '.', ' ') . ' RON' : 'De configurat' }}</td>
                            <td class="number">{{ $dailyCost ? number_format($dailyCost['packaging_cost'], 2, '.', ' ') . ' RON' : '-' }}</td>
                            <td class="number">{{ $dailyCost && ! $dailyCost['has_missing_prices'] ? number_format($dailyCost['total_cost'], 2, '.', ' ') . ' RON' : 'Partial' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section>
            <h2>Planificarea meselor</h2>
            <table>
                <thead><tr><th>Data</th><th>Congregatie</th><th>Fel principal</th><th>Ciorba</th><th>Desert / gustare</th><th>Portii estimate</th></tr></thead>
                <tbody>
                    @foreach ($week->dailyMeals as $dailyMeal)
                        <tr>
                            <td>{{ $dailyMeal->meal_date->translatedFormat('D, j M') }}</td>
                            <td>{{ $dailyMeal->congregation?->name ?? '-' }}</td>
                            <td>{{ $dailyMeal->menu?->name ?? 'Neales' }}</td>
                            <td>{{ $dailyMeal->soupMenu?->name ?? '-' }}</td>
                            <td>{{ $dailyMeal->dessertMenu?->name ?? '-' }}</td>
                            <td class="number">{{ $dailyMeal->estimated_people }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>