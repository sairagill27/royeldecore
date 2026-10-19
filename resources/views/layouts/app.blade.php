<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    {{-- Basic settings every page needs --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Page title. Each page can set its own with @section('title').
         config('app.name') reads the website name from .env (APP_NAME). --}}
    <title>@yield('title', config('app.name') . ' | Home Décor & Inspiration')</title>

    {{-- Short description shown in Google results --}}
    <meta name="description" content="@yield('meta_description', 'Home décor inspiration, products, ideas and tools from ' . config('app.name') . '.')">

    {{-- RSS feed: lets feed readers and search engines find new articles --}}
    <link rel="alternate" type="application/rss+xml" title="{{ config('app.name') }} blog" href="{{ route('blog.feed') }}">

    {{-- Default sharing tags (WhatsApp, Facebook...). A page that sets its own, such as an
         article, adds @section('has_og') and skips these. --}}
    @hasSection('has_og')
    @else
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="@yield('title', config('app.name') . ' | Home Décor & Inspiration')">
    <meta property="og:description" content="@yield('meta_description', 'Home décor inspiration, products, ideas and tools from ' . config('app.name') . '.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('assets/images/logo.png') }}">
    <meta name="twitter:card" content="summary">
    @endif

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

    {{-- Extra tags a page adds (canonical link, structured data...) --}}
    @stack('head')
</head>

<body>
    {{-- Accessibility: keyboard users can press Tab to skip the menu --}}
    <a class="visually-hidden-focusable" href="#main-content">Skip to main content</a>

    {{-- HEADER: loads resources/views/components/header.blade.php --}}
    <x-header />

    {{-- MAIN CONTENT: each page (home, shop, cart...) puts its content here --}}
    <main id="main-content">
        @yield('content')
    </main>

    {{-- FOOTER: loads resources/views/components/footer.blade.php --}}
    <x-footer />

    {{-- POPUP MESSAGES: login, logout, add to cart, errors...
         This loads resources/views/components/flash.blade.php --}}
    <x-flash />

    {{-- Bootstrap JavaScript (makes the mobile menu and dropdowns work) --}}
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    {{-- Extra scripts a page adds --}}
    @stack('scripts')
</body>

</html>