<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Tells search engines never to list admin pages --}}
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') | Admin | {{ config('app.name') }}</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body class="sgh-admin">

    {{-- The page is split into: sidebar (left) + main area (right) --}}
    <div class="sgh-admin-shell">

        {{-- ============ SIDEBAR ============
         Desktop: fixed on the left. Mobile: slides in when the menu button is pressed. --}}
        <aside class="sgh-admin-aside">
            <div class="offcanvas-lg offcanvas-start sgh-admin-offcanvas" tabindex="-1"
                id="adminMenu" aria-labelledby="adminMenuLabel">

                <div class="sgh-admin-sidebar">

                    <div class="sgh-admin-brand">
                        <a href="{{ route('admin.dashboard') }}" id="adminMenuLabel">{{ config('app.name') }}</a>
                        <small>Admin panel</small>
                        {{-- Close button: mobile only --}}
                        <button type="button" class="btn-close btn-close-white d-lg-none"
                            data-bs-dismiss="offcanvas" data-bs-target="#adminMenu" aria-label="Close menu"></button>
                    </div>

                    <nav class="sgh-admin-nav" aria-label="Admin menu">
                        {{-- Working link: turns green when you are on this page --}}
                        <a href="{{ route('admin.dashboard') }}"
                            class="sgh-admin-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>

                        {{-- Not built yet: shown greyed out with a "Soon" label.
                         When a page is ready, we replace the <span> with a real <a href>. --}}
                        <span class="sgh-admin-link disabled"><i class="bi bi-box-seam"></i> Products <em>Soon</em></span>
                        <span class="sgh-admin-link disabled"><i class="bi bi-tags"></i> Categories <em>Soon</em></span>
                        <span class="sgh-admin-link disabled"><i class="bi bi-receipt"></i> Orders <em>Soon</em></span>
                        <span class="sgh-admin-link disabled"><i class="bi bi-people"></i> Customers <em>Soon</em></span>
                    </nav>

                    <div class="sgh-admin-sidebar-foot">
                        <a href="{{ url('/') }}" target="_blank" rel="noopener" class="sgh-admin-link">
                            <i class="bi bi-box-arrow-up-right"></i> View website
                        </a>
                    </div>
                </div>
            </div>
        </aside>

        {{-- ============ MAIN AREA ============ --}}
        <div class="sgh-admin-main">

            {{-- Top bar --}}
            <header class="sgh-admin-topbar">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn sgh-icon-btn d-lg-none" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#adminMenu"
                        aria-controls="adminMenu" aria-label="Open menu">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 class="h5 mb-0">@yield('heading', 'Dashboard')</h1>
                </div>

                <div class="d-flex align-items-center gap-3">
                    {{-- {{ }} escapes the name (safe) --}}
                    <span class="small text-secondary d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-3">Log out</button>
                    </form>
                </div>
            </header>

            {{-- Page content. Messages now appear as toasts (see <x-flash /> below). --}}
            <main class="sgh-admin-content">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- TOAST MESSAGES: saved, deleted, errors...
     This loads resources/views/components/flash.blade.php --}}
    <x-flash />

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>