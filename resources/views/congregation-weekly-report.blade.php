<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fisa {{ $congregation->name }} - Saptamana {{ $week->week_number }}</title>
    <style>
        :root { color: #183c32; background: #f6f6f3; font-family: Arial, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { margin: 0 auto; max-width: 980px; padding: 36px 24px 60px; }
        header { border-bottom: 4px solid #059669; padding-bottom: 20px; }
        .kicker { color: #047857; font-size: .78rem; font-weight: 700; letter-spacing: .08em; margin: 0 0 10px; text-transform: uppercase; }
        h1, h2 { font-family: Georgia, serif; }
        h1 { font-size: 2.2rem; margin: 0; }
        h2 { font-size: 1.45rem; margin: 32px 0 12px; }
        .meta { line-height: 1.55; margin: 12px 0 0; }
        .contact { background: #e4efe8; border-left: 4px solid #059669; margin-top: 18px; padding: 14px 16px; }
        table { background: white; border-collapse: collapse; width: 100%; }
        th { background: #e4efe8; color: #14532d; text-align: left; }
        th, td { border: 1px solid #d6dfd9; padding: 11px 12px; vertical-align: top; }
        .number { text-align: right; white-space: nowrap; }
        .notice { background: #fef3c7; border-left: 4px solid #d97706; padding: 12px 14px; }
        .safety { background: #fff1f2; border: 1px solid #fecdd3; border-left: 4px solid #e11d48; padding: 16px; }
        .safety h3 { color: #9f1239; font-size: 1rem; margin: 0 0 8px; }
        .safety p, .safety li { line-height: 1.45; }
        .safety ul { margin: 8px 0 0; padding-left: 20px; }
        .print { background: #047857; border: 0; color: white; cursor: pointer; font-weight: 700; margin-top: 18px; padding: 10px 16px; }
        @media print { .print { display: none; } main { max-width: none; padding: 0; } }
    </style>
</head>
<body>
    <main>
        <header>
            <p class="kicker">OMS - fisa pentru congregatie</p>
            <h1>{{ $congregation->name }}</h1>
            <p class="meta"><strong>Saptamana {{ $week->week_number }}:</strong> {{ $week->start_date->translatedFormat('j F Y') }} - {{ $week->start_date->addDays(4)->translatedFormat('j F Y') }}</p>
            @if ($congregation->assistant_name)
                <div class="contact"><strong>Asistent responsabil:</strong> {{ $congregation->assistant_name }}@if ($congregation->assistant_phone), {{ $congregation->assistant_phone }}@endif@if ($congregation->assistant_email), {{ $congregation->assistant_email }}@endif</div>
            @endif
            <button class="print" type="button" onclick="window.print()">Printeaza fisa</button>
        </header>

        <section>
            <h2>Zile si portii de pregatit</h2>
            <table>
                <thead><tr><th>Data</th><th>Fel principal si alergeni</th><th>Ciorba si alergeni</th><th class="number">Portii</th></tr></thead>
                <tbody>
                    @forelse ($week->dailyMeals as $dailyMeal)
                        <tr>
                            <td>{{ $dailyMeal->meal_date->translatedFormat('D, j M') }}</td>
                            <td>
                                <strong>{{ $dailyMeal->menu?->name ?? 'Neales' }}</strong><br>
                                <small>Alergeni: {{ $dailyMeal->menu === null ? '-' : (is_array($dailyMeal->menu->allergens) ? ($dailyMeal->menu->allergens === [] ? 'Niciunul declarat' : implode(', ', $dailyMeal->menu->allergens)) : 'De confirmat') }}</small>
                            </td>
                            <td>
                                @if ($dailyMeal->soupMenu)
                                    <strong>{{ $dailyMeal->soupMenu->name }}</strong><br>
                                    <small>Alergeni: {{ is_array($dailyMeal->soupMenu->allergens) ? ($dailyMeal->soupMenu->allergens === [] ? 'Niciunul declarat' : implode(', ', $dailyMeal->soupMenu->allergens)) : 'De confirmat' }}</small>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="number">{{ $dailyMeal->estimated_people }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Aceasta congregatie nu are zile alocate in saptamana selectata.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="safety">
            <h3>Siguranta alimentara - de citit inainte de pregatire</h3>
            <p><strong>Persoanele cu alergii sau intolerante trebuie sa verifice lista de alergeni inainte de servire si sa anunte asistentul responsabil.</strong> Nu serviti un preparat daca nu sunteti siguri de ingrediente sau de riscul de contaminare incrucisata.</p>
            <ul>
                <li>Spalati mainile cu apa si sapun inainte de pregatire si dupa manipularea carnii crude.</li>
                <li>Folositi ustensile si tocatoare separate pentru carne cruda, legume si preparatele gata de servire.</li>
                <li>Gatiti complet carnea si pastrati mancarea calda pana la livrare; raciti rapid orice preparat care nu este servit imediat.</li>
                <li>Nu folositi ingrediente cu ambalaj deteriorat, termen depasit sau provenienta neclara.</li>
                <li>Pentru portii speciale, folositi recipiente si ustensile curate, separate, si etichetati clar preparatul.</li>
                <li>Actualizati alergenele din reteta daca se schimba un ingredient, un furnizor sau o reteta.</li>
            </ul>
        </section>

        <section>
            <h2>Lista de cumparaturi</h2>
            <table>
                <thead><tr><th>Ingredient</th><th class="number">Cantitate</th><th class="number">Pret estimat</th><th class="number">Cost estimat</th></tr></thead>
                <tbody>
                    @forelse ($shoppingList['ingredients'] as $ingredient)
                        <tr><td>{{ $ingredient['name'] }}</td><td class="number">{{ rtrim(rtrim(number_format($ingredient['quantity'], 3, '.', ''), '0'), '.') }} {{ $ingredient['unit'] }}</td><td class="number">{{ $ingredient['has_missing_price'] ? 'De configurat' : number_format($ingredient['estimated_unit_cost'], 2, '.', ' ') . ' RON/' . $ingredient['unit'] }}</td><td class="number">{{ $ingredient['has_missing_price'] ? '-' : number_format($ingredient['estimated_cost'], 2, '.', ' ') . ' RON' }}</td></tr>
                    @empty
                        <tr><td colspan="4">Nu exista meniuri complete pentru aceasta congregatie.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <p><strong>Ambalaje:</strong> {{ $shoppingList['packaging']['count'] }} caserole. <strong>Cost ambalaje:</strong> {{ number_format($shoppingList['packaging']['total_cost'], 2, '.', ' ') }} RON.</p>
            <p><strong>Cost ingrediente configurat:</strong> {{ number_format($shoppingList['totals']['ingredients_cost'], 2, '.', ' ') }} RON. <strong>Total estimat configurat:</strong> {{ number_format($shoppingList['totals']['total_cost'], 2, '.', ' ') }} RON.</p>
            @if ($shoppingList['totals']['has_missing_prices'])<p class="notice">Unele ingrediente nu au pret estimat. Totalul este partial pana la completarea preturilor.</p>@endif
        </section>
    </main>
</body>
</html>