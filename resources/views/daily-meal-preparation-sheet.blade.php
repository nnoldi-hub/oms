<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fisa pregatire {{ $dailyMeal->meal_date->format('d.m.Y') }}</title>
    <style>
        :root { color: #183c32; background: #f6f6f3; font-family: Arial, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { margin: 0 auto; max-width: 880px; padding: 36px 24px 60px; }
        header { border-bottom: 4px solid #059669; padding-bottom: 20px; }
        .kicker { color: #047857; font-size: .78rem; font-weight: 700; letter-spacing: .08em; margin: 0 0 10px; text-transform: uppercase; }
        h1, h2 { font-family: Georgia, serif; }
        h1 { font-size: 2.2rem; margin: 0; }
        h2 { font-size: 1.45rem; margin: 30px 0 12px; }
        .meta { line-height: 1.55; margin: 12px 0 0; }
        .recipe { background: #e4efe8; border-left: 4px solid #059669; padding: 14px 16px; }
        .recipe--soup { background: #fff5df; border-color: #d97706; }
        .recipe p { margin: 7px 0 0; }
        table { background: white; border-collapse: collapse; width: 100%; }
        th { background: #e4efe8; color: #14532d; text-align: left; }
        th, td { border: 1px solid #d6dfd9; padding: 11px 12px; vertical-align: top; }
        .number { text-align: right; white-space: nowrap; }
        .notice { background: #fef3c7; border-left: 4px solid #d97706; padding: 12px 14px; }
        .safety { background: #fff1f2; border: 1px solid #fecdd3; border-left: 4px solid #e11d48; padding: 16px; }
        .safety h3 { color: #9f1239; font-size: 1rem; margin: 0 0 8px; }
        .safety p { line-height: 1.45; }
        .print { background: #047857; border: 0; color: white; cursor: pointer; font-weight: 700; margin-top: 18px; padding: 10px 16px; }
        @media print { .print { display: none; } main { max-width: none; padding: 0; } }
    </style>
</head>
<body>
    <main>
        <header>
            <p class="kicker">Organizare masa pentru santier - fisa de pregatire</p>
            <h1>{{ $dailyMeal->meal_date->translatedFormat('l, j F Y') }}</h1>
            <p class="meta"><strong>Congregatie:</strong> {{ $dailyMeal->congregation?->name ?? 'Nealocata' }}<br><strong>Portii de pregatit:</strong> {{ $dailyMeal->estimated_people }}</p>
            <button class="print" type="button" onclick="window.print()">Printeaza fisa zilei</button>
        </header>

        @foreach ([['label' => 'Fel principal', 'menu' => $dailyMeal->menu, 'requirements' => $mainRequirements, 'class' => ''], ['label' => 'Ciorba suplimentara', 'menu' => $dailyMeal->soupMenu, 'requirements' => $soupRequirements, 'class' => 'recipe--soup']] as $section)
            @if ($section['menu'] && $section['requirements'])
                <section>
                    <h2>{{ $section['label'] }}</h2>
                    <div class="recipe {{ $section['class'] }}">
                        <strong>{{ $section['menu']->name }}</strong>
                        <p><strong>Instructiuni:</strong> {{ $section['menu']->instructions ?: 'De stabilit de echipa de bucatarie.' }}</p>
                        <p><strong>Alergeni:</strong> {{ is_array($section['menu']->allergens) ? ($section['menu']->allergens === [] ? 'Niciunul declarat' : implode(', ', $section['menu']->allergens)) : 'De confirmat' }}</p>
                    </div>
                    <table>
                        <thead><tr><th>Ingredient</th><th class="number">Cantitate pentru {{ $dailyMeal->estimated_people }} portii</th><th class="number">Cost estimat</th></tr></thead>
                        <tbody>
                            @foreach ($section['requirements']['ingredients'] as $ingredient)
                                <tr><td>{{ $ingredient['name'] }}</td><td class="number">{{ rtrim(rtrim(number_format($ingredient['quantity'], 3, '.', ''), '0'), '.') }} {{ $ingredient['unit'] }}</td><td class="number">{{ $ingredient['has_missing_price'] ? 'De configurat' : number_format($ingredient['estimated_cost'], 2, '.', ' ') . ' RON' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p><strong>Ambalaje:</strong> {{ $section['requirements']['packaging']['count'] }} caserole. <strong>Cost ambalaje:</strong> {{ number_format($section['requirements']['packaging']['total_cost'], 2, '.', ' ') }} RON.</p>
                    @if ($section['requirements']['totals']['has_missing_prices'])<p class="notice">Unele ingrediente nu au pret estimat; totalul este partial.</p>@endif
                </section>
            @endif
        @endforeach

        <section class="safety">
            <h3>Siguranta alimentara</h3>
            <p><strong>Verificati alergenii inainte de pregatire si servire.</strong> Folositi ustensile separate pentru carne cruda, legume si preparate gata de servire; spalati mainile si pastrati preparatele calde pana la livrare.</p>
        </section>
    </main>
</body>
</html>