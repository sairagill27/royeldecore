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

    {{-- How many contact messages are still unread (shown as a badge in the menu) --}}
    @php
    $unreadMessages = \App\Models\ContactMessage::whereNull('read_at')->count();
    $pendingComments = \App\Models\Comment::where('status', 'pending')->count();
    $pendingReviews  = \App\Models\ProductReview::where('status', 'pending')->count();
    @endphp

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

                    {{-- MENU: each link turns green when you are on that section --}}
                    <nav class="sgh-admin-nav" aria-label="Admin menu">

                        <a href="{{ route('admin.dashboard') }}"
                            class="sgh-admin-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>

                        <a href="{{ route('admin.products.index') }}"
                            class="sgh-admin-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                            <i class="bi bi-box-seam"></i> Products
                        </a>

                        <a href="{{ route('admin.categories.index') }}"
                            class="sgh-admin-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                            <i class="bi bi-tags"></i> Categories
                        </a>

                        <a href="{{ route('admin.orders.index') }}"
                            class="sgh-admin-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                            <i class="bi bi-receipt"></i> Orders
                        </a>

                        <a href="{{ route('admin.customers.index') }}"
                            class="sgh-admin-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                            <i class="bi bi-people"></i> Customers
                        </a>

                        <a href="{{ route('admin.posts.index') }}"
                            class="sgh-admin-link {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}">
                            <i class="bi bi-journal-text"></i> Blog posts
                        </a>
                        <a href="{{ route('admin.reviews.index') }}"
                       class="sgh-admin-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                        <i class="bi bi-star"></i> Reviews
                        @if ($pendingReviews > 0)
                            <span class="sgh-count" aria-label="{{ $pendingReviews }} waiting">{{ $pendingReviews }}</span>
                        @endif
                    </a>

                    <a href="{{ route('admin.comments.index') }}"
                            class="sgh-admin-link {{ request()->routeIs('admin.comments.*') ? 'active' : '' }}">
                            <i class="bi bi-chat-dots"></i> Comments
                            @if ($pendingComments > 0)
                            <span class="sgh-count" aria-label="{{ $pendingComments }} waiting">{{ $pendingComments }}</span>
                            @endif
                        </a>
                        <a href="{{ route('admin.messages.index') }}"
                            class="sgh-admin-link {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}">
                            <i class="bi bi-envelope"></i> Messages
                            @if ($unreadMessages > 0)
                            <span class="sgh-count" aria-label="{{ $unreadMessages }} unread">{{ $unreadMessages }}</span>
                            @endif
                        </a>
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

            {{-- Page content. Messages appear as popups (see <x-flash /> below). --}}
            <main class="sgh-admin-content">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- POPUP MESSAGES: saved, deleted, errors... --}}
    <x-flash />

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>