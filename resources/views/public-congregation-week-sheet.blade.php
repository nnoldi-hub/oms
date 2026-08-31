<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Program mese {{ $congregation->name }}</title>
    <style>
        :root { color: #183c32; background: #f6f6f3; font-family: Arial, sans-serif; } * { box-sizing: border-box; } body { margin: 0; } main { margin: 0 auto; max-width: 960px; padding: 32px 20px 56px; } header { border-bottom: 4px solid #059669; padding-bottom: 18px; } .kicker { color: #047857; font-size: .78rem; font-weight: 700; letter-spacing: .08em; margin: 0 0 8px; text-transform: uppercase; } h1, h2 { font-family: Georgia, serif; } h1 { font-size: 2.1rem; margin: 0; } h2 { font-size: 1.4rem; margin: 30px 0 12px; } .dates { margin: 10px 0 0; } table { background: #fff; border-collapse: collapse; width: 100%; } th { background: #e4efe8; color: #14532d; text-align: left; } th, td { border: 1px solid #d6dfd9; padding: 10px; vertical-align: top; } .number { text-align: right; white-space: nowrap; } .notice { background: #fef3c7; border-left: 4px solid #d97706; padding: 12px 14px; } .print { background: #047857; border: 0; color: #fff; cursor: pointer; font-weight: 700; margin-top: 16px; padding: 10px 16px; } @media print { .print { display: none; } main { max-width: none; padding: 0; } } @media (max-width: 640px) { main { padding: 24px 12px 40px; } th, td { padding: 8px; } }
    </style>
</head>
<body><main>
    <header>
        <p class="kicker">Organizare masa pentru santier</p>
        <h1>{{ $congregation->name }}</h1>
        <p class="dates"><strong>Saptamana {{ $week->week_number }}:</strong> {{ $week->start_date->translatedFormat('j F Y') }} - {{ $week->start_date->addDays(4)->translatedFormat('j F Y') }}</p>
        <button class="print" type="button" onclick="window.print()">Printeaza fisa</button>
    </header>
    <section>
        <h2>Zile si preparate</h2>
        <table><thead><tr><th>Data</th><th>Fel principal</th><th>Ciorba</th><th>Desert / gustare</th><th class="number">Portii</th></tr></thead><tbody>
        @forelse ($week->dailyMeals as $dailyMeal)
            <tr><td>{{ $dailyMeal->meal_date->translatedFormat('D, j M') }}</td><td><strong>{{ $dailyMeal->menu?->name ?? 'Neales' }}</strong><br><small>Alergeni: {{ $dailyMeal->menu && is_array($dailyMeal->menu->allergens) ? ($dailyMeal->menu->allergens === [] ? 'Niciunul declarat' : implode(', ', $dailyMeal->menu->allergens)) : 'De confirmat' }}</small></td><td>{{ $dailyMeal->soupMenu?->name ?? '-' }}</td><td>{{ $dailyMeal->dessertMenu?->name ?? '-' }}</td><td class="number">{{ $dailyMeal->estimated_people }}</td></tr>
        @empty
            <tr><td colspan="5">Nu exista zile alocate in aceasta saptamana.</td></tr>
        @endforelse
        </tbody></table>
    </section>
    <section>
        <h2>Lista de cumparaturi</h2>
        <table><thead><tr><th>Ingredient</th><th class="number">Cantitate</th></tr></thead><tbody>
        @forelse ($shoppingList['ingredients'] as $ingredient)
            <tr><td>{{ $ingredient['name'] }}</td><td class="number">{{ rtrim(rtrim(number_format($ingredient['quantity'], 3, '.', ''), '0'), '.') }} {{ $ingredient['unit'] }}</td></tr>
        @empty
            <tr><td colspan="2">Nu exista ingrediente pentru zilele selectate.</td></tr>
        @endforelse
        </tbody></table>
            @if ($shoppingList['incomplete_meals'] > 0)
                <p class="notice">{{ $shoppingList['incomplete_meals'] }} zile nu au fel principal ales si nu sunt incluse in lista.</p>
            @endif
    </section>
</main></body></html>