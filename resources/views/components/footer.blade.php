{{-- FOOTER: background color is set in style.css (.sgh-footer) --}}
<footer class="sgh-footer mt-5">

    {{-- PART 1: NEWSLETTER STRIP at the top of the footer --}}
    <div class="sgh-footer-newsletter">
        <div class="container py-4">
            <div class="row align-items-center g-3">

                <div class="col-lg-6">
                    <h2 class="h4 text-white mb-1">Get fresh décor ideas</h2>
                    <p class="mb-0 small">Join our newsletter for décor inspiration, tips and new arrivals.</p>
                </div>

                <div class="col-lg-6">
                    {{-- @csrf protects the form (security rule) --}}
                    <form action="{{ route('newsletter.subscribe') }}" method="POST">
                        @csrf

                        {{-- SPAM TRAP: people never see this field. If it is filled in, a bot did it. --}}
                        <div class="sgh-hp" aria-hidden="true">
                            <label for="nl-company">Leave this field empty</label>
                            <input type="text" id="nl-company" name="company" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="input-group">
                            <label for="newsletter-email" class="visually-hidden">Email address</label>
                            <input type="email" id="newsletter-email" name="email" maxlength="255"
                                class="form-control" placeholder="Your email address" required>
                            <button type="submit" class="btn btn-light px-4">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- PART 2: MAIN LINK COLUMNS --}}
    <div class="container py-5">
        <div class="row g-4">

            {{-- Column 1: Brand --}}
            <div class="col-lg-4 col-md-12">
                <a href="{{ url('/') }}" class="sgh-footer-logo-box d-inline-block">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="{{ config('app.name') }}" height="44">
                </a>
                <p class="mt-3 small pe-lg-5">
                    Thoughtful home décor ideas, practical tools, and beautiful décor
                    products to help you create a home you love.
                </p>

                {{-- Social icons: replace each "#" with your real page link later --}}
                <div class="d-flex gap-2">
                    <a href="#" class="sgh-social" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="sgh-social" aria-label="Pinterest"><i class="bi bi-pinterest"></i></a>
                    <a href="#" class="sgh-social" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                </div>
            </div>

            {{-- Column 2: Shop --}}
            <div class="col-lg-2 col-6 offset-lg-1">
                <h2 class="h6 sgh-footer-title">Shop</h2>
                <ul class="list-unstyled small">
                    <li><a href="{{ url('/shop') }}">All Products</a></li>
                    <li><a href="{{ url('/shop/wall-decor') }}">Wall Décor</a></li>
                    <li><a href="{{ url('/shop/mirrors') }}">Mirrors</a></li>
                    <li><a href="{{ url('/shop/islamic-decor') }}">Islamic Décor</a></li>
                    <li><a href="{{ url('/cart') }}">Cart</a></li>
                </ul>
            </div>

            {{-- Column 3: Explore (Blog and Tools pages are built later) --}}
            <div class="col-lg-2 col-6">
                <h2 class="h6 sgh-footer-title">Explore</h2>
                <ul class="list-unstyled small">
                    <li><a href="{{ url('/blog') }}">Blog</a></li>
                    <li><a href="{{ url('/tools') }}">Décor Tools</a></li>
                    <li><a href="{{ route('search') }}">Search</a></li>
                </ul>
            </div>

            {{-- Column 4: Company --}}
            <div class="col-lg-2 col-6">
                <h2 class="h6 sgh-footer-title">Company</h2>
                <ul class="list-unstyled small">
                    <li><a href="{{ url('/about') }}">About Us</a></li>
                    <li><a href="{{ url('/contact') }}">Contact</a></li>
                    <x-contact-details />
                    <li><a href="{{ url('/account') }}">My Account</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- PART 3: BOTTOM BAR (copyright + legal links) --}}
    <div class="sgh-footer-bottom">
        <div class="container py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small">

                {{-- date('Y') prints the current year automatically --}}
                <span>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>

                <ul class="list-inline mb-0">
                    <li class="list-inline-item"><a href="{{ url('/privacy-policy') }}">Privacy Policy</a></li>
                    <li class="list-inline-item"><a href="{{ url('/terms') }}">Terms &amp; Conditions</a></li>
                    <li class="list-inline-item"><a href="{{ url('/disclaimer') }}">Disclaimer</a></li>
                    <li class="list-inline-item"><a href="{{ url('/affiliate-disclosure') }}">Affiliate Disclosure</a></li>
                    <li class="list-inline-item"><a href="{{ url('/cookies') }}">Cookies</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>