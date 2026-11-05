{{-- FEATURED CATEGORIES: real categories from the database ($homeCategories).
     A category with a photo shows the photo; otherwise it shows an icon.
     $categoryCounts says how many visible products each category has. --}}

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
@endphp

@if ($homeCategories->isNotEmpty())
<section class="sgh-section">
    <div class="container">

        <div class="text-center mb-5">
            <p class="sgh-eyebrow mb-2">Shop by category</p>
            <h2 class="sgh-section-title">Find the perfect piece for every space</h2>
        </div>

        {{-- 2 per row on phones, 3 on tablets, 6 on large screens --}}
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3 g-lg-4">
            @foreach ($homeCategories as $category)
                @php $count = (int) ($counts[$category->id] ?? 0); @endphp
                <div class="col">
                    <a href="{{ url('/shop/' . $category->slug) }}" class="sgh-cat-card">
                        <span class="sgh-cat-icon">
                            @if ($category->image)
                                <img src="{{ asset('storage/' . $category->image) }}" alt=""
                                     width="76" height="76" loading="lazy">
                            @else
                                <i class="bi {{ $icons[$category->slug] ?? 'bi-grid' }}" aria-hidden="true"></i>
                            @endif
                        </span>
                        {{-- {{ }} escapes the text (safe) --}}
                        <span class="sgh-cat-name">{{ $category->name }}</span>
                        @if ($count > 0)
                            <span class="sgh-cat-count">{{ $count }} {{ \Illuminate\Support\Str::plural('piece', $count) }}</span>
                        @endif
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif