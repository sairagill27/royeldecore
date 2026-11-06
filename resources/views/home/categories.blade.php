{{-- SHOP BY CATEGORY: real categories from the database ($homeCategories).
     Each card shows a photo (or an icon), the name, a short description and a piece count.
     The grid class sgh-collections--nN (N = how many cards) lets the layout fit any number of categories. --}}

@php
    // Icon used when a category has no photo yet. Unknown slugs get the grid icon.
    $icons = [
        'wall-decor'         => 'bi-image',
        'mirrors'            => 'bi-bounding-box-circles',
        'islamic-decor'      => 'bi-moon-stars',
        'wall-clocks'        => 'bi-clock',
        'decorative-pieces'  => 'bi-flower1',
        'organizers'         => 'bi-grid',
        'personalized-decor' => 'bi-pencil-square',
    ];
    $counts = $categoryCounts ?? collect();
    $total  = $homeCategories->count();
@endphp

@if ($total > 0)
<section class="sgh-section">
    <div class="container">

        <div class="text-center mb-5">
            <p class="sgh-eyebrow mb-2">Shop by category</p>
            <h2 class="sgh-section-title">Find the perfect piece for every space</h2>
            <span class="sgh-rule" aria-hidden="true"></span>
            <p class="sgh-section-sub">Browse our collections and find what suits your home.</p>
        </div>

        <div class="sgh-collections sgh-collections--n{{ $total }}">
            @foreach ($homeCategories as $category)
                @php
                    $count = (int) ($counts[$category->id] ?? 0);
                    $about = trim(\Illuminate\Support\Str::limit(strip_tags((string) $category->description), 95));
                @endphp

                <a href="{{ url('/shop/' . $category->slug) }}" class="sgh-collection">

                    <div class="sgh-collection-media">
                        @if ($category->image)
                            <img src="{{ asset('storage/' . $category->image) }}" alt=""
                                 width="800" height="600" loading="lazy" decoding="async">
                        @else
                            <i class="bi {{ $icons[$category->slug] ?? 'bi-grid' }}" aria-hidden="true"></i>
                        @endif

                        @if ($count > 0)
                            <span class="sgh-collection-count">{{ $count }} {{ \Illuminate\Support\Str::plural('piece', $count) }}</span>
                        @endif
                    </div>

                    <div class="sgh-collection-body">
                        {{-- {{ }} escapes the text, so nothing in a name or description can run as code --}}
                        <h3 class="sgh-collection-name">{{ $category->name }}</h3>
                        <p class="sgh-collection-text">{{ $about !== '' ? $about : 'Explore our ' . $category->name . ' collection.' }}</p>
                        <span class="sgh-collection-go">Shop now <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="text-center mt-4 pt-2">
            <a href="{{ url('/shop') }}" class="btn btn-outline-primary rounded-pill px-4">Browse the whole shop</a>
        </div>
    </div>
</section>
@endif