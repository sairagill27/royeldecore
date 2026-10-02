<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    {{-- Basic settings every page needs --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Page title. Each page can set its own with @section('title'). --}}
    <title>@yield('title', 'SGHomeTrends | Home Décor & Inspiration')</title>

    {{-- Short description shown in Google results --}}
    <meta name="description" content="@yield('meta_description', 'Home décor inspiration, products, ideas and tools from SGHomeTrends.')">

    {{-- Fonts: Inter for text, Playfair Display for headings --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap CSS (ready-made styles for layout, buttons, menus) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous">

    {{-- Bootstrap Icons (search, cart, person icons) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- OUR CSS: brand colors. Loaded last so it can override Bootstrap. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body>
    {{-- Accessibility: keyboard users can press Tab to skip the menu --}}
    <a class="visually-hidden-focusable" href="#main-content">Skip to main content</a>

    {{-- HEADER: loads resources/views/components/header.blade.php --}}
    <x-header />

    {{-- MAIN CONTENT: each page (like home) puts its content here --}}
    <main id="main-content">
        @yield('content')
    </main>

    {{-- FOOTER: loads resources/views/components/footer.blade.php --}}
    <x-footer />

    {{-- Bootstrap JavaScript (makes the mobile menu and dropdowns work) --}}
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>