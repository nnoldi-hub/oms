<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $dailyMeal->menu->name }} - OMS</title>
    <style>
        :root { color: #17352c; background: #f5f3ec; font-family: Georgia, serif; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { width: min(100% - 32px, 720px); margin: 0 auto; padding: 40px 0 56px; }
        header { border-bottom: 3px solid #d7653c; padding-bottom: 24px; }
        .eyebrow { color: #c14b28; font-family: Arial, sans-serif; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; margin: 0 0 12px; text-transform: uppercase; }
        h1 { font-size: clamp(2rem, 7vw, 3.5rem); font-weight: 500; line-height: 1.05; margin: 0; }
        .date { font-family: Arial, sans-serif; font-size: 1rem; margin: 14px 0 0; }
        section { margin-top: 32px; }
        h2 { font-size: 1.3rem; font-weight: 600; margin: 0 0 12px; }
        .instructions { font-size: 1.1rem; line-height: 1.6; white-space: pre-line; }
        .list { background: #fffdf8; border: 1px solid #ddd5c5; border-radius: 6px; list-style: none; margin: 0; padding: 0; }
        .list li { display: flex; font-family: Arial, sans-serif; gap: 20px; justify-content: space-between; padding: 15px 16px; }
        .list li + li { border-top: 1px solid #e8e2d6; }
        .quantity { color: #bd4826; font-weight: 700; white-space: nowrap; }
        .packaging { background: #dce8df; border-left: 4px solid #2e7158; padding: 18px 20px; }
        .packaging p { font-family: Arial, sans-serif; line-height: 1.5; margin: 0; }
    </style>
</head>
<body>
    <main>
        <header>
            <p class="eyebrow">Organizare mese santier</p>
            <h1>{{ $dailyMeal->menu->name }}</h1>
            <p class="date">{{ $dailyMeal->meal_date->translatedFormat('l, j F Y') }}</p>
        </header>

        @if ($dailyMeal->menu->instructions)
            <section>
                <h2>Instructiuni</h2>
                <div class="instructions">{{ $dailyMeal->menu->instructions }}</div>
            </section>
        @endif

        @if ($dailyMeal->soupMenu)
            <section>
                <h2>Ciorba saptamanii</h2>
                <p class="instructions">{{ $dailyMeal->soupMenu->name }}</p>
            </section>
        @endif

        <section>
            <h2>Lista de cumparaturi</h2>
            <ul class="list">
                @foreach ($requirements['ingredients'] as $ingredient)
                    <li>
                        <span>{{ $ingredient['name'] }}</span>
                        <span class="quantity">{{ rtrim(rtrim(number_format($ingredient['quantity'], 3, '.', ''), '0'), '.') }} {{ $ingredient['unit'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="packaging">
            <h2>Ambalaje</h2>
            <p>Sunt necesare {{ $requirements['packaging']['count'] }} caserole.</p>
        </section>
    </main>
</body>
</html>