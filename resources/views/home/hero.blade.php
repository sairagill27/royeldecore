{{-- HERO SECTION (Editorial Split): text on the left, image on the right --}}
<section class="sgh-hero">
    <div class="container">
        {{-- align-items-center: centers text vertically next to the image --}}
        <div class="row align-items-center g-4 g-lg-5">
            {{-- LEFT COLUMN: the text. On mobile it appears first. --}}
            <div class="col-lg-6">
                {{-- Small label above the heading --}}
                <p class="sgh-eyebrow mb-3">Home décor inspiration</p>
                {{-- Main heading (the serif font comes from style.css) --}}
                <h1 class="sgh-hero-title mb-3">
                    Make every room feel <span class="sgh-hero-accent">like home.</span>
                </h1>
                {{-- Short description --}}
                <p class="lead text-secondary mb-4">
                    Discover thoughtful décor ideas, shop beautiful pieces for your walls
                    and shelves, and plan your space with our free décor tools.
                </p>
                {{-- BUTTONS: the main one first, a quieter one second --}}
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ url('/shop') }}" class="btn btn-primary btn-lg rounded-pill px-4">
                        Explore Décor <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    <a href="{{ url('/tools') }}" class="btn btn-outline-primary btn-lg rounded-pill px-4">
                        Try Décor Tools
                    </a>
                </div>
                {{-- THREE SMALL BENEFITS (no fake numbers, just things the site offers) --}}
                <ul class="list-unstyled d-flex flex-wrap gap-3 gap-md-4 small text-secondary mb-0">
                    <li><i class="bi bi-check-circle-fill text-primary me-1"></i> Curated décor pieces</li>
                    <li><i class="bi bi-check-circle-fill text-primary me-1"></i> Free room calculators</li>
                    <li><i class="bi bi-check-circle-fill text-primary me-1"></i> Ideas for every budget</li>
                </ul>
            </div>
            {{-- RIGHT COLUMN: the image --}}
            <div class="col-lg-6">
                <div class="sgh-hero-media position-relative">
                    {{-- width/height stop the page from jumping while loading.
                         loading="eager" because this image is at the top of the page.
                         Write a real description in alt for your photo. --}}
                    <img src="{{ asset('assets/images/hero.jpg.jfif') }}"
                         alt="Bright living room styled with wall art, a mirror and decorative pieces"
                         class="sgh-hero-img"
                         width="1200" height="1400"
                         loading="eager" fetchpriority="high">
                    {{-- Small floating card on top of the image --}}
                    <a href="{{ url('/tools/room-budget-planner') }}"
                       class="sgh-hero-card text-decoration-none">
                        <span class="sgh-hero-card-icon"><i class="bi bi-calculator"></i></span>
                        <span>
                            <strong class="d-block">Room Budget Planner</strong>
                            <small class="text-secondary">Plan your décor spending</small>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>