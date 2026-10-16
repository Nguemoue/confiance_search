<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/images/logo-confiance.png" type="image/png">
<link rel="apple-touch-icon" href="/images/logo-confiance.png">

<!-- Google Fonts: Geist & Manrope (Saasover Design System) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="{{ config('saasover.fonts.google_fonts_url', 'https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap') }}" rel="stylesheet">

<!-- Font Awesome & Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
