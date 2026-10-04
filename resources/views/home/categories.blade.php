{{-- FEATURED CATEGORIES: icon cards (we can swap icons for photos later) --}}

@php
// The list of categories. To add or change one, edit a line here.
// 'icon' is a Bootstrap Icons name, 'slug' is the end of the shop URL.
$featuredCategories = [
['name' => 'Wall Décor', 'slug' => 'wall-decor', 'icon' => 'bi-image'],
['name' => 'Mirrors', 'slug' => 'mirrors', 'icon' => 'bi-bounding-box-circles'],
['name' => 'Islamic Décor', 'slug' => 'islamic-decor', 'icon' => 'bi-moon-stars'],
['name' => 'Wall Clocks', 'slug' => 'wall-clocks', 'icon' => 'bi-clock'],
['name' => 'Decorative Pieces', 'slug' => 'decorative-pieces', 'icon' => 'bi-flower1'],
];
@endphp

<section class="sgh-section">
    <div class="container">

        {{-- Section heading --}}
        <div class="text-center mb-5">
            <p class="sgh-eyebrow mb-2">Shop by category</p>
            <h2 class="sgh-section-title">Find the perfect piece for every space</h2>
        </div>

        {{-- CARDS: 2 per row on phones, 3 on tablets, 5 on large screens --}}
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3 g-lg-4">

            {{-- Loop: creates one card for each category in the list above --}}
            @foreach ($featuredCategories as $category)
            <div class="col">
                <a href="{{ url('/shop/' . $category['slug']) }}" class="sgh-cat-card">
                    <span class="sgh-cat-icon">
                        <i class="bi {{ $category['icon'] }}"></i>
                    </span>
                    <span class="sgh-cat-name">{{ $category['name'] }}</span>
                </a>
            </div>
            @endforeach

        </div>
    </div>
</section>