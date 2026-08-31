<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Raport retete si costuri</title>
    <style>
        :root { color: #183c32; background: #f6f6f3; font-family: Arial, sans-serif; } * { box-sizing: border-box; } body { margin: 0; } main { margin: 0 auto; max-width: 1080px; padding: 36px 24px 60px; } header { border-bottom: 4px solid #059669; padding-bottom: 20px; } .kicker { color: #047857; font-size: .78rem; font-weight: 700; letter-spacing: .08em; margin: 0 0 10px; text-transform: uppercase; } h1, h2 { font-family: Georgia, serif; } h1 { font-size: 2.2rem; margin: 0; } h2 { font-size: 1.4rem; margin: 30px 0 10px; } table { background: #fff; border-collapse: collapse; width: 100%; } th { background: #e4efe8; text-align: left; } th, td { border: 1px solid #d6dfd9; padding: 10px; vertical-align: top; } .number { text-align: right; white-space: nowrap; } .notice { background: #fef3c7; border-left: 4px solid #d97706; padding: 10px 12px; } .print { background: #047857; border: 0; color: #fff; cursor: pointer; font-weight: 700; margin-top: 18px; padding: 10px 16px; } @media print { .print { display: none; } main { max-width: none; padding: 0; } }
    </style>
</head>
<body><main>
    <header><p class="kicker">Organizare masa pentru santier</p><h1>Raport retete si costuri</h1><button class="print" type="button" onclick="window.print()">Printeaza raportul</button></header>
    @foreach (['main' => 'Feluri principale', 'soup' => 'Ciorbe', 'dessert' => 'Deserturi si gustari'] as $type => $heading)
        <section><h2>{{ $heading }}</h2><table><thead><tr><th>Reteta</th><th>Ingrediente per portie</th><th class="number">Cost ingrediente</th><th class="number">Ambalaj</th><th class="number">Total / portie</th></tr></thead><tbody>
        @forelse ($menus->filter(fn (array $item): bool => $item['menu']->type === $type) as $item)
            <tr><td><strong>{{ $item['menu']->name }}</strong><br><small>{{ $item['menu']->is_active ? 'Activa' : 'Inactiva' }}</small></td><td>{{ collect($item['requirements']['ingredients'])->map(fn (array $ingredient): string => $ingredient['name'] . ' (' . rtrim(rtrim(number_format($ingredient['quantity'], 3, '.', ''), '0'), '.') . ' ' . $ingredient['unit'] . ')')->implode(', ') }}</td><td class="number">{{ $item['requirements']['totals']['has_missing_prices'] ? 'De configurat' : number_format($item['requirements']['totals']['ingredients_cost'], 2, '.', ' ') . ' RON' }}</td><td class="number">{{ number_format($item['requirements']['packaging']['total_cost'], 2, '.', ' ') }} RON</td><td class="number">{{ $item['requirements']['totals']['has_missing_prices'] ? 'Partial' : number_format($item['requirements']['totals']['total_cost'], 2, '.', ' ') . ' RON' }}</td></tr>
        @empty
            <tr><td colspan="5">Nu exista retete in aceasta categorie.</td></tr>
        @endforelse
        </tbody></table></section>
    @endforeach
</main></body></html>