<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Donații - OMS</title>
    <style>
        :root { color: #17352c; background: #f5f3ec; font-family: Georgia, serif; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { width: min(100% - 32px, 720px); margin: 0 auto; padding: 40px 0 56px; }
        header { border-bottom: 3px solid #d7653c; padding-bottom: 22px; }
        .eyebrow { color: #c14b28; font-family: Arial, sans-serif; font-size: .75rem; font-weight: 700; letter-spacing: .08em; margin: 0 0 12px; text-transform: uppercase; }
        h1 { font-size: clamp(2rem, 7vw, 3.2rem); font-weight: 500; line-height: 1.05; margin: 0; }
        .invitation { background: #fffdf8; border: 1px solid #ddd5c5; border-radius: 6px; font-size: 1.08rem; line-height: 1.65; margin: 30px 0; padding: 22px; }
        .contact { background: #dce8df; border-left: 4px solid #2e7158; padding: 18px 20px; }
        .contact p { font-family: Arial, sans-serif; line-height: 1.5; margin: 0; }
        .contact a { color: #14532d; font-weight: 700; }
    </style>
</head>
<body>
    <main>
        <header>
            <p class="eyebrow">Organizare mese santier</p>
            <h1>Donații de alimente</h1>
        </header>

        <section class="invitation">{{ $invitation }}</section>

        @if ($contactPhone !== '')
            <section class="contact">
                <p><a href="tel:{{ $contactPhone }}">{{ $contactName !== '' ? $contactName : 'Contact donații' }} · {{ $contactPhone }}</a></p>
            </section>
        @endif
    </main>
</body>
</html>
