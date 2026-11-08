{{-- SHOP BY CATEGORY: real categories from the database ($homeCategories), shown as Bootstrap cards.
     The whole card is clickable (stretched-link on the button). $categoryCounts = visible products per category. --}}

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

    // Four categories fit one row of four. Any other number uses three per row, and the last row is centred.
    $col = $total === 4 ? 'col-lg-3' : 'col-lg-4';
@endphp

@if ($total > 0)
<section class="sgh-section sgh-cats">
    <div class="container">

        <div class="text-center mb-5">
            <p class="sgh-eyebrow mb-2">Shop by category</p>
            <h2 class="sgh-section-title">Find the perfect piece for <em>every space</em></h2>
            <span class="sgh-rule" aria-hidden="true"></span>
            <p class="sgh-section-sub">Browse our collections and find what suits your home.</p>
        </div>

        <div class="row g-4 justify-content-center sgh-collections sgh-collections--n{{ $total }}">
            @foreach ($homeCategories as $category)
                @php
                    $count = (int) ($counts[$category->id] ?? 0);
                    $about = trim(\Illuminate\Support\Str::limit(strip_tags((string) $category->description), 95));
                @endphp

                <div class="col-12 col-sm-6 {{ $col }}">
                    <div class="card sgh-collection h-100 border-0 shadow-sm rounded-4 overflow-hidden">

                        {{-- Photo (or an icon), with the piece count in the corner --}}
                        <div class="position-relative overflow-hidden">
                            <div class="ratio ratio-4x3" style="background: var(--sgh-soft-sage);">
                                @if ($category->image)
                                    <img src="{{ asset('storage/' . $category->image) }}" alt=""
                                         class="object-fit-cover" width="800" height="600" loading="lazy" decoding="async">
                                @else
                                    <div class="d-flex align-items-center justify-content-center fs-1 text-success">
                                        <i class="bi {{ $icons[$category->slug] ?? 'bi-grid' }}" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>

                            @if ($count > 0)
                                <span class="badge rounded-pill text-bg-light position-absolute top-0 start-0 m-3 shadow-sm sgh-collection-count">
                                    {{ $count }} {{ \Illuminate\Support\Str::plural('piece', $count) }}
                                </span>
                            @endif
                        </div>

                        <div class="card-body d-flex flex-column p-4">
                            {{-- {{ }} escapes the text, so nothing in a name or description can run as code --}}
                            <h3 class="card-title h5">{{ $category->name }}</h3>
                            <p class="card-text text-secondary small">{{ $about !== '' ? $about : 'Explore our ' . $category->name . ' collection.' }}</p>

                            <a href="{{ url('/shop/' . $category->slug) }}"
                               class="btn sgh-btn-dark rounded-pill px-4 mt-auto align-self-start stretched-link">
                                Shop now <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-5">
            <a href="{{ url('/shop') }}" class="btn btn-outline-primary rounded-pill px-4">Browse the whole shop</a>
        </div>
    </div>
</section>
@endif