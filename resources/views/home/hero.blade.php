{{-- HOME HERO (magazine style): text on the left, photo on the right that fades into the background,
     and leaf branches on the left edge. The leaves are drawn with SVG (no image files). --}}

@php
    // The photo: the first of these files that exists in public/assets/images
    $heroFile = collect(['hero.webp', 'hero.jpg', 'hero.jpeg', 'hero.png', 'hero.jpg.jfif', 'hero.jfif'])
        ->first(fn ($file) => file_exists(public_path('assets/images/' . $file)));

    // Read aloud by screen readers. Change it to describe your real photo.
    $heroAlt = 'A calm living room styled with natural décor';

    // The first 4 visible categories (HomeController sends $homeCategories)
    $chips = ($homeCategories ?? collect())->take(4);
@endphp

<section class="sgh-banner">

    {{-- The leaf drawing, defined once and used twice below --}}
    <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
        <defs>
            <g id="sgh-leaf">
                <path d="M0 0C8 -10 28 -12 44 0C28 12 8 10 0 0Z"/>
                <path d="M3 0L38 0" fill="none" stroke="#fff" stroke-opacity=".45" stroke-width="1"/>
            </g>
            <g id="sgh-branch">
                <path d="M30 0C60 80 10 160 50 260" fill="none" stroke="#7c9c82" stroke-width="2.2" stroke-linecap="round"/>
                <use href="#sgh-leaf" class="sgh-leaf-a" transform="translate(39 36) rotate(35) scale(.85)"/>
                <use href="#sgh-leaf" class="sgh-leaf-b" transform="translate(40 72) rotate(148)"/>
                <use href="#sgh-leaf" class="sgh-leaf-a" transform="translate(37 110) rotate(30) scale(1.1)"/>
                <use href="#sgh-leaf" class="sgh-leaf-b" transform="translate(34 148) rotate(152) scale(1.05)"/>
                <use href="#sgh-leaf" class="sgh-leaf-a" transform="translate(34 188) rotate(28)"/>
                <use href="#sgh-leaf" class="sgh-leaf-b" transform="translate(40 230) rotate(155) scale(.9)"/>
                <use href="#sgh-leaf" class="sgh-leaf-a" transform="translate(50 260) rotate(85) scale(.8)"/>
            </g>
        </defs>
    </svg>

    {{-- Decorative branches on the left edge --}}
    <svg class="sgh-banner-leaves sgh-banner-leaves--top" viewBox="-30 0 170 280" aria-hidden="true" focusable="false"><use href="#sgh-branch"/></svg>
    <svg class="sgh-banner-leaves sgh-banner-leaves--low" viewBox="-30 0 170 280" aria-hidden="true" focusable="false"><use href="#sgh-branch"/></svg>

    {{-- TEXT --}}
    <div class="sgh-banner-inner">
        <div class="container">
            <div class="sgh-banner-copy">

                <p class="sgh-banner-eyebrow">Premium Home Décor</p>

                <h1 class="sgh-banner-title">Beautiful Spaces Start with the Right <em>Décor</em></h1>

                <p class="sgh-banner-text">Transform your home with unique décor pieces, designed to bring warmth, style and inspiration to every corner.</p>

                <div class="sgh-banner-actions">
                    <a href="{{ route('shop') }}" class="btn sgh-btn-dark rounded-pill px-4 py-2">Shop Now <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                    <a href="{{ route('blog.index') }}" class="btn sgh-btn-ghost rounded-pill px-4 py-2">Explore Ideas</a>
                </div>

                {{-- Search the shop --}}
                <form action="{{ route('search') }}" method="GET" role="search" class="sgh-banner-search">
                    <label for="hero-q" class="visually-hidden">Search the shop</label>
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="hero-q" name="q" maxlength="100" autocomplete="off" placeholder="Search mirrors, wall art, clocks&hellip;">
                    <button type="submit" class="btn sgh-btn-dark rounded-pill px-4">Search</button>
                </form>

                {{-- Shop by category (real categories from the database) --}}
                @if ($chips->isNotEmpty())
                    <div class="sgh-banner-chips">
                        <span class="sgh-banner-chips-label">Shop by</span>
                        @foreach ($chips as $chip)
                            <a href="{{ route('shop', ['category' => $chip->slug]) }}" class="sgh-banner-chip">{{ $chip->name }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- PHOTO (the left edge fades into the background) --}}
    <div class="sgh-banner-photo">
        @if ($heroFile)
            <img src="{{ asset('assets/images/' . $heroFile) }}" alt="{{ $heroAlt }}"
                 width="1600" height="900" loading="eager" fetchpriority="high">
        @else
            <div class="sgh-banner-photo-empty" aria-hidden="true"><i class="bi bi-image"></i></div>
        @endif
    </div>
</section>