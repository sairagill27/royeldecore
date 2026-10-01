<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <title>@yield('title', 'SGHomeTrends | Home Décor & Inspiration')</title>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS v5.3.8 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- SGHomeTrends CSS -->
    <link rel="stylesheet"
        href="{{ asset('assets/css/style.css') }}">

</head>

<body>
    <!-- SGHomeTrends Navbar -->
    <nav class="navbar navbar-expand-lg navbar-sgh">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="{{ url('/') }}">
                <img src="{{ asset('assets/images/logo.png') }}"
                    alt="SGHomeTrends Logo"
                    class="sgh-logo">
            </a>
            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavbar"
                aria-controls="mainNavbar"
                aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <!-- Navbar Content -->
            <div class="collapse navbar-collapse" id="mainNavbar">
                <!-- Main Links -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/') }}">
                            Home
                        </a>
                    </li>
                    <!-- Inspiration -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown">
                            Inspiration </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#">
                                    Rooms & Spaces </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    Home Tours </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    Small Spaces </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    Before & After </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    Color Ideas </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Styles -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown">
                            Styles </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#">
                                    Modern </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    Minimalist </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    Scandinavian </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    Japandi </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    Luxury </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            Blog </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            Tools </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            Shop </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-ai" href="#">
                            AI Designer </a>
                    </li>
                </ul>
                <!-- Right Actions -->
                <div class="d-flex align-items-center sgh-header-actions">
                    <a href="#"
                        class="sgh-icon-link"
                        aria-label="Search">
                        <i class="bi bi-search"></i>
                    </a>
                    <a href="#"
                        class="sgh-icon-link"
                        aria-label="Favorites">
                        <i class="bi bi-heart"></i>
                    </a>
                    <a href="#"
                        class="sgh-icon-link"
                        aria-label="Shopping Bag">
                        <i class="bi bi-bag"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main> @yield('content') </main>
    <!-- SGHomeTrends Footer -->
    <footer class="sgh-footer">
        <div class="container">
            <div class="row g-5">
                <!-- Brand -->
                <div class="col-lg-4 col-md-6">
                    <a href="{{ url('/') }}"
                        class="sgh-footer-brand">
                        <img src="{{ asset('assets/images/logo.png') }}"
                            alt="SGHomeTrends Logo"
                            class="sgh-footer-logo">
                    </a>
                    <p class="sgh-footer-description">
                        Discover thoughtful home décor ideas, inspiring spaces,
                        practical tools, and beautiful products to help you
                        create a home you love.
                    </p>
                    <!-- Social Icons -->
                    <div class="sgh-social-links">
                        <a href="#"
                            aria-label="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="#"
                            aria-label="Pinterest">
                            <i class="bi bi-pinterest"></i>
                        </a>
                        <a href="#"
                            aria-label="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#"
                            aria-label="YouTube">
                            <i class="bi bi-youtube"></i>
                        </a>
                    </div>
                </div>
                <!-- Explore -->
                <div class="col-lg-2 col-md-6">
                    <h5 class="sgh-footer-title"> Explore </h5>
                    <ul class="sgh-footer-links">
                        <li>
                            <a href="#">Inspiration</a>
                        </li>
                        <li>
                            <a href="#">Styles</a>
                        </li>
                        <li>
                            <a href="#">Blog</a>
                        </li>
                        <li>
                            <a href="#">Home Tours</a>
                        </li>
                        <li>
                            <a href="#">Before & After</a>
                        </li>
                    </ul>
                </div>
                <!-- Resources -->
                <div class="col-lg-2 col-md-6">
                    <h5 class="sgh-footer-title"> Resources </h5>
                    <ul class="sgh-footer-links">
                        <li>
                            <a href="#">Tools</a>
                        </li>
                        <li>
                            <a href="#">AI Designer</a>
                        </li>
                        <li>
                            <a href="#">Shop</a>
                        </li>
                        <li>
                            <a href="#">DIY Ideas</a>
                        </li>
                        <li>
                            <a href="#">Color Ideas</a>
                        </li>
                    </ul>
                </div>
                <!-- Company -->
                <div class="col-lg-2 col-md-6">
                    <h5 class="sgh-footer-title"> Company </h5>
                    <ul class="sgh-footer-links">
                        <li>
                            <a href="#">About Us</a>
                        </li>
                        <li>
                            <a href="#">Contact</a>
                        </li>
                        <li>
                            <a href="#">Privacy Policy</a>
                        </li>
                        <li>
                            <a href="#">Terms & Conditions</a>
                        </li>
                        <li>
                            <a href="#">Disclaimer</a>
                        </li>
                    </ul>
                </div>
                <!-- Newsletter -->
                <div class="col-lg-2 col-md-12">
                    <h5 class="sgh-footer-title"> Stay Inspired </h5>
                    <p class="sgh-footer-small-text">
                        Get fresh home décor ideas delivered to your inbox.
                    </p>
                    <a href="#"
                        class="sgh-footer-newsletter-btn"> Join Newsletter
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
            <!-- Footer Bottom -->
            <div class="sgh-footer-bottom">
                <p class="mb-0"> © {{ date('Y') }} SGHomeTrends. All rights reserved. </p>
                <div class="sgh-footer-bottom-links">
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                    <a href="#">Contact</a>
                </div>
            </div>
        </div>
    </footer>
    <!-- Bootstrap JavaScript Bundle (includes Popper) -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>

</html>