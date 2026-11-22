<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sprijin pe congregatii</title>
    <style>
        :root { color: #183c32; background: #f6f6f3; font-family: Arial, sans-serif; } * { box-sizing: border-box; } body { margin: 0; } main { margin: 0 auto; max-width: 1180px; padding: 32px 20px 56px; } h1 { font-family: Georgia, serif; font-size: 1.9rem; margin: 0; } h2 { font-family: Georgia, serif; font-size: 1.2rem; margin: 26px 0 10px; } .meta { color: #4b5563; margin: 8px 0 18px; } form.filters { background: #fff; border: 1px solid #d6dfd9; display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; padding: 12px; } label { display: flex; flex-direction: column; font-size: .85rem; gap: 4px; } input { border: 1px solid #cbd5d1; padding: 7px 9px; } button { background: #047857; border: 0; color: #fff; cursor: pointer; font-weight: 700; padding: 9px 14px; } table { background: #fff; border-collapse: collapse; width: 100%; } th { background: #e4efe8; color: #14532d; } th, td { border: 1px solid #d6dfd9; font-size: .88rem; padding: 8px 10px; text-align: left; vertical-align: top; } td.number { text-align: right; white-space: nowrap; } tfoot td { background: #eef5f0; font-weight: 700; } .print { margin: 0 0 16px; } @media print { .print, form.filters { display: none; } main { max-width: none; padding: 0; } } @media (max-width: 760px) { .table-wrap { overflow-x: auto; } table { min-width: 820px; } }
    </style>
</head>
<body><main>
    <h1>Sprijin pe congregatii</h1>
    <p class="meta">Perioada: {{ $from ?? 'tot programul' }} – {{ $to ?? 'tot programul' }}</p>

    @if (! $pdf)
        <form class="filters" method="GET" action="{{ route('congregation-support-reports.show') }}">
            <label>De la <input type="date" name="from" value="{{ $from }}"></label>
            <label>Pana la <input type="date" name="to" value="{{ $to }}"></label>
            <button type="submit">Filtreaza</button>
        </form>
        <button class="print" type="button" onclick="window.print()">Printeaza raportul</button>
    @endif

    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Congregatie</th>
                <th>Rol</th>
                <th class="number">Zile contributie</th>
                <th class="number">Zile gatit</th>
                <th class="number">Donatii</th>
                <th class="number">Valoare donatii</th>
                <th class="number">Donatii alimente</th>
                <th class="number">Consumabile</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row['congregation']->name }}</td>
                <td>{{ $row['type'] === \App\Support\CongregationRole::COOKS ? 'Gateste' : 'Contribuie' }}</td>
                <td class="number">{{ $row['contribution_days'] }}</td>
                <td class="number">{{ $row['cooking_days'] }}</td>
                <td class="number">{{ $row['donations_count'] }}</td>
                <td class="number">{{ number_format($row['donated_amount'], 2, '.', ' ') }} RON</td>
                <td class="number">{{ $row['food_donations_count'] }}</td>
                <td class="number">{{ $row['supply_count'] }}</td>
                <td>{{ $row['support_notes'] ?: '-' }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td class="number">{{ $totals['contribution_days'] }}</td>
                <td class="number">{{ $totals['cooking_days'] }}</td>
                <td class="number">{{ $totals['donations_count'] }}</td>
                <td class="number">{{ number_format($totals['donated_amount'], 2, '.', ' ') }} RON</td>
                <td class="number">{{ $totals['food_donations_count'] }}</td>
                <td class="number">{{ $totals['supply_count'] }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    </div>
</main></body>
</html>
