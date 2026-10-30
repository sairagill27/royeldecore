{{-- HEADER: stays at the top while scrolling --}}
<header class="sgh-header sticky-top bg-white">

    {{-- MAIN NAVBAR: full menu on large screens, hamburger on small screens --}}
    <nav class="navbar navbar-expand-lg py-3" aria-label="Main navigation">
        <div class="container-fluid px-3 px-lg-4">

            {{-- 1. LOGO: size is controlled by .sgh-logo in style.css --}}
            <a class="navbar-brand me-lg-4" href="{{ url('/') }}">
                <img src="{{ asset('assets/images/logo.png') }}" alt="{{ config('app.name') }}" class="sgh-logo">
            </a>

            {{-- 2. HAMBURGER BUTTON: only visible on mobile/tablet --}}
            <button class="navbar-toggler border-0 shadow-none" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            {{-- 3. MENU AREA --}}
            <div class="collapse navbar-collapse" id="mainNavbar">

                {{-- LEFT SIDE: main links (me-auto pushes the rest to the right) --}}
                <ul class="navbar-nav me-auto mb-3 mb-lg-0 gap-lg-1">

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('shop*') ? 'active' : '' }}" href="{{ url('/shop') }}">Shop</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('blog*') ? 'active' : '' }}" href="{{ url('/blog') }}">Blog</a>
                    </li>

                    {{-- CATEGORIES DROPDOWN: opens on hover (desktop) or tap (mobile) --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">Categories</a>
                        <ul class="dropdown-menu sgh-dropdown">
                            <li><a class="dropdown-item" href="{{ url('/shop/wall-decor') }}">Wall Décor</a></li>
                            <li><a class="dropdown-item" href="{{ url('/shop/mirrors') }}">Mirrors</a></li>
                            <li><a class="dropdown-item" href="{{ url('/shop/islamic-decor') }}">Islamic Décor</a></li>
                            <li><a class="dropdown-item" href="{{ url('/shop/wall-clocks') }}">Wall Clocks</a></li>
                            <li><a class="dropdown-item" href="{{ url('/shop/decorative-pieces') }}">Decorative Pieces</a></li>
                            <li><a class="dropdown-item" href="{{ url('/shop/organizers') }}">Organizers</a></li>
                            <li><a class="dropdown-item" href="{{ url('/shop/personalized-decor') }}">Personalized Décor</a></li>
                        </ul>
                    </li>

                    {{-- TOOLS DROPDOWN (the calculator pages are built later) --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('tools*') ? 'active' : '' }}" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">Tools</a>
                        <ul class="dropdown-menu sgh-dropdown">
                            <li><a class="dropdown-item" href="{{ url('/tools/paint-calculator') }}">Paint Calculator</a></li>
                            <li><a class="dropdown-item" href="{{ url('/tools/flooring-calculator') }}">Flooring Calculator</a></li>
                            <li><a class="dropdown-item" href="{{ url('/tools/wallpaper-calculator') }}">Wallpaper Calculator</a></li>
                            <li><a class="dropdown-item" href="{{ url('/tools/curtain-calculator') }}">Curtain Calculator</a></li>
                            <li><a class="dropdown-item" href="{{ url('/tools/room-budget-planner') }}">Room Budget Planner</a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('about') ? 'active' : '' }}" href="{{ url('/about') }}">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('contact') ? 'active' : '' }}" href="{{ url('/contact') }}">Contact</a>
                    </li>
                </ul>

                {{-- RIGHT SIDE: search, cart, and login/account (gap-3 = space between items) --}}
                <div class="d-flex align-items-center gap-3">

                    {{-- SEARCH ICON: opens the search bar below the menu --}}
                    <button class="btn sgh-icon-btn" type="button"
                        data-bs-toggle="collapse" data-bs-target="#searchBar"
                        aria-controls="searchBar" aria-expanded="false" aria-label="Open search">
                        <i class="bi bi-search"></i>
                    </button>

                    {{-- CART: the number shows only when the cart has items.
                         Note the closing </a> right after @endif. --}}
                    <a href="{{ url('/cart') }}" class="btn sgh-icon-btn position-relative" aria-label="Shopping cart">
                        <i class="bi bi-bag"></i>
                        @php $cartCount = (int) array_sum(session('cart', [])); @endphp
                        @if ($cartCount > 0)
                        <span class="badge rounded-pill bg-primary position-absolute top-0 start-100 translate-middle">{{ $cartCount }}</span>
                        @endif
                    </a>

                    {{-- Visitors NOT logged in: two buttons with a gap between them --}}
                    @guest
                    <div class="d-flex gap-2 ms-lg-2">
                        <a href="{{ url('/login') }}" class="btn btn-outline-primary rounded-pill px-3">Login</a>
                        <a href="{{ url('/register') }}" class="btn btn-primary rounded-pill px-3">Register</a>
                    </div>
                    @endguest

                    {{-- Logged-in customers: account dropdown --}}
                    @auth
                    <div class="dropdown">
                        <a href="#" class="btn sgh-icon-btn" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                            <i class="bi bi-person-circle"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end sgh-dropdown">
                            <li><a class="dropdown-item" href="{{ url('/account') }}">My Account</a></li>
                            <li><a class="dropdown-item" href="{{ url('/account/orders') }}">My Orders</a></li>
                                <li><a class="dropdown-item" href="{{ route('wishlist.index') }}">My Wishlist</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                {{-- Logout must be a form with @csrf for security --}}
                                <form method="POST" action="{{ url('/logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    @endauth

                </div> {{-- end of RIGHT SIDE --}}
            </div> {{-- end of MENU AREA --}}
        </div> {{-- end of container --}}
    </nav>

    {{-- SEARCH BAR: hidden until the search icon is clicked.
         It must stay HERE, after </nav> and before </header>. --}}
    <div class="collapse border-top" id="searchBar">
        <div class="container py-3">
            <form action="{{ url('/search') }}" method="GET" role="search">
                <div class="d-flex align-items-center gap-2">

                    <div class="input-group input-group-lg">
                        <input type="search" name="q" class="form-control"
                            placeholder="Search décor products, ideas and articles…" aria-label="Search">
                        <button class="btn btn-primary px-4" type="submit">Search</button>
                    </div>

                    {{-- CLOSE BUTTON (X): click it to hide the search bar --}}
                    <button class="btn sgh-icon-btn" type="button"
                        data-bs-toggle="collapse" data-bs-target="#searchBar"
                        aria-controls="searchBar" aria-label="Close search">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>