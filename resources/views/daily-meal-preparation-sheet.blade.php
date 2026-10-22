<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fișă pregătire {{ $dailyMeal->meal_date->format('d.m.Y') }}</title>
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
        .check { text-align: center; white-space: nowrap; width: 34px; }
        .check input { height: 16px; width: 16px; }
        .notice { background: #fef3c7; border-left: 4px solid #d97706; padding: 12px 14px; }
        .budget { background: #e4efe8; border-left: 4px solid #047857; line-height: 1.55; padding: 14px 16px; }
        .budget p { margin: 4px 0; }
        .safety { background: #fff1f2; border: 1px solid #fecdd3; border-left: 4px solid #e11d48; padding: 16px; }
        .safety h3 { color: #9f1239; font-size: 1rem; margin: 0 0 8px; }
        .safety p { line-height: 1.45; }
        .crew { background: white; border-left: 4px solid #059669; line-height: 1.55; padding: 14px 16px; }
        .crew p { margin: 4px 0; }
        .crew__role { color: #047857; font-weight: 700; }
        .crew__phone { color: #4b5563; }
        .crew__missing { color: #be123c; font-weight: 700; }
        .delivery { background: #eef2ff; border-left: 4px solid #4338ca; line-height: 1.55; margin-top: 10px; padding: 14px 16px; }
        .print { background: #047857; border: 0; color: white; cursor: pointer; font-weight: 700; margin-top: 18px; padding: 10px 16px; }
        @media print { .print { display: none; } main { max-width: none; padding: 0; } }
    </style>
</head>
<body>
    <main>
        <header>
            <p class="kicker">Organizare masă pentru șantier - fișă de pregătire</p>
            <h1>{{ $dailyMeal->meal_date->translatedFormat('l, j F Y') }}</h1>
            <p class="meta">
                <strong>Congregație:</strong> {{ $dailyMeal->congregation?->name ?? 'Nealocată' }}<br>
                <strong>Porții de pregătit:</strong> {{ $dailyMeal->portions() }}<br>
                <strong>Ora de livrare pe șantier:</strong> {{ filled($dailyMeal->delivery_time) ? $dailyMeal->delivery_time : 'de stabilit' }}
            </p>
            <button class="print" type="button" onclick="window.print()">Printează fișa zilei</button>
        </header>

        <section>
            <h2>Echipe pe roluri și livrare</h2>
            <div class="crew">
                @forelse ($roles as $role)
                    <p>
                        <span class="crew__role">{{ $role['label'] }}:</span>
                        @foreach ($role['people'] as $person)
                            {{ $person['name'] }}@if ($person['phone']) <span class="crew__phone">({{ $person['phone'] }})</span>@endif{{ $loop->last ? '' : ', ' }}
                        @endforeach
                    </p>
                @empty
                    <p>Nu sunt persoane alocate pe roluri pentru această zi.</p>
                @endforelse
                @if ($uncoveredRoles !== [])
                    <p class="crew__missing">Roluri neacoperite: {{ implode(', ', $uncoveredRoles) }}</p>
                @endif
            </div>
            <div class="delivery">
                <strong>Ora de livrare pe șantier:</strong> {{ filled($dailyMeal->delivery_time) ? $dailyMeal->delivery_time : 'de stabilit' }}
            </div>
        </section>

        @foreach (['main' => ['label' => 'Fel principal', 'class' => ''], 'soup' => ['label' => 'Ciorbă', 'class' => 'recipe--soup'], 'dessert' => ['label' => 'Desert / gustare', 'class' => 'recipe--soup']] as $componentKey => $meta)
            @php
                $section = $components[$componentKey];
            @endphp
            @if ($section['menu'] && $section['requirements'])
                @php
                    $allergenText = $section['menu']->allergensLabel();
                    $packaging = $section['requirements']['packaging'];
                    $hasPackaging = $componentKey !== 'soup' && (float) $packaging['total_cost'] > 0;
                @endphp
                <section>
                    <h2>{{ $meta['label'] }}</h2>
                    <div class="recipe {{ $meta['class'] }}">
                        <strong>{{ $section['menu']->name }}</strong>
                        <p><strong>Instrucțiuni:</strong> {{ $section['menu']->instructions ?: 'De stabilit de echipa de bucătărie.' }}</p>
                        <p><strong>Alergeni:</strong> {{ $allergenText }}</p>
                    </div>
                    <table>
                        <thead><tr><th class="check"></th><th>Ingredient</th><th class="number">Cantitate pentru {{ $dailyMeal->portions() }} porții</th><th class="number">Cost estimat</th></tr></thead>
                        <tbody>
                            @foreach ($section['requirements']['ingredients'] as $ingredient)
                                <tr>
                                    <td class="check"><input type="checkbox" aria-label="De cumparat {{ $ingredient['name'] }}"></td>
                                    <td>{{ $ingredient['name'] }}</td>
                                    <td class="number">{{ $ingredient['needs_configuration'] ? 'De configurat' : rtrim(rtrim(number_format($ingredient['quantity'], 3, '.', ''), '0'), '.') . ' ' . $ingredient['unit'] }}</td>
                                    <td class="number">{{ $ingredient['has_missing_price'] ? 'De configurat' : number_format($ingredient['estimated_cost'], 2, '.', ' ') . ' RON' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p>
                        @if ($hasPackaging)
                            <strong>Ambalaje:</strong> {{ $packaging['count'] }} caserole. <strong>Cost ambalaje:</strong> {{ number_format($packaging['total_cost'], 2, '.', ' ') }} RON.
                        @elseif ($componentKey === 'soup')
                            <strong>Ambalaje:</strong> fără ambalaj (ambalaj comun cu felul principal).
                        @else
                            <strong>Ambalaje:</strong> fără ambalaj.
                        @endif
                    </p>
                    @if ($section['requirements']['totals']['has_missing_prices'])<p class="notice">Unele ingrediente nu au preț estimat; totalul este parțial.</p>@endif
                </section>
            @endif
        @endforeach

        <section>
            <h2>Totaluri și de cumpărat</h2>
            <div class="budget">
                <p>
                    <strong>Total ingrediente:</strong> {{ number_format($shoppingList['totals']['ingredients_cost'], 2, '.', ' ') }} RON.
                    <strong>Total ambalaje:</strong> {{ number_format($shoppingList['totals']['packaging_cost'], 2, '.', ' ') }} RON.
                    <strong>Total estimat:</strong> {{ number_format($shoppingList['totals']['total_cost'], 2, '.', ' ') }} RON.
                </p>
                @if ($shoppingList['cost_per_portion'] !== null)
                    <p><strong>Cost pe porție:</strong> {{ number_format($shoppingList['cost_per_portion'], 2, '.', ' ') }} RON / porție.</p>
                @else
                    <p class="notice">Costul pe porție este parțial: unele ingrediente nu au preț configurat.</p>
                @endif
            </div>
            <table>
                <thead><tr><th class="check"></th><th>De cumpărat</th><th class="number">Cantitate</th><th class="number">Cantitate de cumpărat</th><th class="number">Preț estimat</th><th class="number">Cost estimat</th></tr></thead>
                <tbody>
                    @forelse ($shoppingList['ingredients'] as $ingredient)
                        <tr>
                            <td class="check"><input type="checkbox" aria-label="De cumparat {{ $ingredient['name'] }}"></td>
                            <td>{{ $ingredient['name'] }}</td>
                            <td class="number">{{ $ingredient['needs_configuration'] ? 'De configurat' : rtrim(rtrim(number_format($ingredient['quantity'], 3, '.', ''), '0'), '.') . ' ' . $ingredient['unit'] }}</td>
                            <td class="number">{{ $ingredient['needs_configuration'] ? 'De configurat' : \App\Support\PracticalQuantity::format($ingredient['to_buy_quantity'], $ingredient['unit']) }}</td>
                            <td class="number">{{ $ingredient['has_missing_price'] ? 'De configurat' : number_format($ingredient['estimated_unit_cost'], 2, '.', ' ') . ' RON/' . $ingredient['unit'] }}</td>
                            <td class="number">{{ $ingredient['has_missing_price'] ? '—' : number_format($ingredient['estimated_cost'], 2, '.', ' ') . ' RON' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Nu există meniuri complete pentru această zi.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($shoppingList['totals']['has_missing_prices'])
                <p class="notice">Unele ingrediente nu au preț estimat. Totalul afișat este parțial până la completarea prețurilor din meniuri.</p>
            @endif
        </section>

        @if ($dailyMeal->maximum_budget !== null || $dailyMeal->contributor_count !== null)
            <section>
                <h2>Buget și contribuție</h2>
                <div class="budget">
                    @if ($dailyCost['has_missing_prices'])
                        <p>Completează prețurile ingredientelor pentru a calcula bugetul și contribuția.</p>
                    @else
                        @if ($dailyMeal->portions() > 0)
                            <p><strong>Cost estimat total:</strong> {{ number_format($dailyCost['total_cost'], 2, '.', ' ') }} RON, {{ number_format($dailyCost['total_cost'] / $dailyMeal->portions(), 2, '.', ' ') }} RON/porție.</p>
                        @else
                            <p><strong>Cost estimat total:</strong> {{ number_format($dailyCost['total_cost'], 2, '.', ' ') }} RON.</p>
                        @endif
                        @if ($dailyMeal->maximum_budget !== null && $dailyMeal->portions() > 0 && $dailyCost['total_cost'] > 0)
                            <p><strong>Buget maxim:</strong> {{ number_format($dailyMeal->maximum_budget, 2, '.', ' ') }} RON. <strong>Porții posibile:</strong> {{ (int) floor((float) $dailyMeal->maximum_budget / ($dailyCost['total_cost'] / $dailyMeal->portions())) }}. <strong>Diferența:</strong> {{ number_format((float) $dailyMeal->maximum_budget - $dailyCost['total_cost'], 2, '.', ' ') }} RON.</p>
                        @endif
                        @if ($dailyMeal->contributor_count)
                            <p><strong>Contribuție recomandată:</strong> {{ number_format($dailyCost['total_cost'] / $dailyMeal->contributor_count, 2, '.', ' ') }} RON pentru fiecare dintre cele {{ $dailyMeal->contributor_count }} persoane.</p>
                        @endif
                    @endif
                </div>
            </section>
        @endif

        <section class="safety">
            <h3>Siguranță alimentară</h3>
            <p><strong>Verificați alergenii înainte de pregătire și servire.</strong> Folosiți ustensile separate pentru carne crudă, legume și preparate gata de servire; spălați mâinile și păstrați preparatele calde până la livrare.</p>
        </section>
    </main>
</body>
</html>
