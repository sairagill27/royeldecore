@extends('layouts.app')

@php
    $siteName = config('app.name');
    $currency = config('shop.currency');
    $heading  = $category ? $category->name : 'Shop Décor';
    $intro    = $category && $category->description
        ? $category->description
        : 'Beautiful pieces for your walls, shelves and every corner of your home.';

    $hasMin   = request('min') !== null && request('min') !== '';
    $hasMax   = request('max') !== null && request('max') !== '';
    $filtered = $hasMin || $hasMax || request()->boolean('in_stock') || $sort !== 'newest';

    // The one official address of this page. Filters and sorting never count; page 2, 3... keep their number.
    $canonical = url()->current() . ($products->currentPage() > 1 ? '?page=' . $products->currentPage() : '');
@endphp

@section('title', ($category ? $category->name : 'Shop') . ' | ' . config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($intro), 155))

@push('head')
    <link rel="canonical" href="{{ $canonical }}">
    {{-- Filtered and sorted views are near-copies of the main page, so search engines skip them --}}
    @if ($filtered)
        <meta name="robots" content="noindex, follow">
    @endif
@endpush

@section('content')

{{-- =====================================================
     HEADER: deep green, in the shop's own colours (same as the décor tools)
     ===================================================== --}}
<section class="sgh-tool-head sgh-shop-head">
    @include('tools._leaves')

    <div class="container-fluid px-3 px-lg-4 px-xl-5 position-relative">

        {{-- Breadcrumb: Home > Shop > Category --}}
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/shop') }}">Shop</a></li>
                @if ($category)
                    <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
                @endif
            </ol>
        </nav>

        <div class="sgh-shop-head-row">
            <div>
                <span class="sgh-tool-badge">
                    <i class="bi bi-bag-heart" aria-hidden="true"></i>
                    {{ $category ? 'Collection' : 'The ' . $siteName . ' shop' }}
                </span>
                <h1 class="sgh-tool-head-title">{{ $heading }}</h1>
                <p class="sgh-tool-head-text">{{ $intro }}</p>
            </div>

            {{-- Product count --}}
            <div class="sgh-shop-count">
                <strong>{{ $products->total() }}</strong>
                <span>{{ \Illuminate\Support\Str::plural('product', $products->total()) }}</span>
            </div>
        </div>

        {{-- Category pills: scroll sideways on phones --}}
        <nav class="sgh-pills" aria-label="Shop categories">
            <a href="{{ url('/shop') }}" class="sgh-pill {{ ! $category ? 'active' : '' }}">All</a>
            @foreach ($categories as $cat)
                <a href="{{ url('/shop/' . $cat->slug) }}"
                   class="sgh-pill {{ $category && $category->id === $cat->id ? 'active' : '' }}">{{ $cat->name }}</a>
            @endforeach
        </nav>
    </div>
</section>


{{-- =====================================================
     FILTER PANEL (left) + PRODUCTS (right)
     ===================================================== --}}
<section class="py-3 py-lg-4">
    <div class="container-fluid px-3 px-lg-4 px-xl-5">
        <div class="row g-3 g-lg-4">

            {{-- ============ LEFT: STICKY FILTER PANEL ============
                 Desktop: stays in view while the products scroll. The Apply button stays pinned at its bottom.
                 Mobile: slides in from the side (opened by the Filters button). --}}
            <aside class="col-lg-auto sgh-sidebar-col">
                <div class="sgh-sidebar">
                    <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="filterPanel"
                         aria-labelledby="filterPanelLabel">

                        {{-- Panel header: only visible on mobile --}}
                        <div class="offcanvas-header border-bottom">
                            <h2 class="h5 mb-0" id="filterPanelLabel">Filters</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                    data-bs-target="#filterPanel" aria-label="Close"></button>
                        </div>

                        <div class="offcanvas-body d-block">

                            {{-- CATEGORIES (click the title to fold it away) --}}
                            <details class="sgh-fold sgh-filter-box mb-3" open>
                                <summary>
                                    <span><i class="bi bi-grid" aria-hidden="true"></i> Categories</span>
                                    <i class="bi bi-chevron-down sgh-fold-arrow" aria-hidden="true"></i>
                                </summary>
                                <ul class="list-unstyled mb-0 sgh-cat-list">
                                    <li>
                                        <a href="{{ url('/shop') }}" class="sgh-filter-link {{ ! $category ? 'active' : '' }}"
                                           @if (! $category) aria-current="page" @endif>
                                            <span>All products</span><i class="bi bi-chevron-right" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                    @foreach ($categories as $cat)
                                        @php $isActive = $category && $category->id === $cat->id; @endphp
                                        <li>
                                            <a href="{{ url('/shop/' . $cat->slug) }}" class="sgh-filter-link {{ $isActive ? 'active' : '' }}"
                                               @if ($isActive) aria-current="page" @endif>
                                                <span>{{ $cat->name }}</span><i class="bi bi-chevron-right" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>

                            {{-- FILTERS (a normal GET form, so the link can be shared) --}}
                            <form method="GET" action="{{ url()->current() }}" id="filterForm" class="sgh-filter-box">

                                <h2 class="sgh-filter-title"><i class="bi bi-tag" aria-hidden="true"></i> Price ({{ $currency }})</h2>
                                <div class="sgh-price-grid">
                                    <div>
                                        <label for="priceMin">From</label>
                                        <input type="number" id="priceMin" name="min" min="0" inputmode="numeric"
                                               class="form-control form-control-sm" placeholder="Min" value="{{ request('min') }}">
                                    </div>
                                    <div>
                                        <label for="priceMax">To</label>
                                        <input type="number" id="priceMax" name="max" min="0" inputmode="numeric"
                                               class="form-control form-control-sm" placeholder="Max" value="{{ request('max') }}">
                                    </div>
                                </div>

                                <h2 class="sgh-filter-title"><i class="bi bi-box-seam" aria-hidden="true"></i> Availability</h2>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" name="in_stock"
                                           value="1" id="inStock" {{ request()->boolean('in_stock') ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="inStock">In stock only</label>
                                </div>

                                {{-- Pinned to the bottom of the panel, so it is always in reach --}}
                                <div class="sgh-filter-actions">
                                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Apply filters</button>
                                    <a href="{{ url()->current() }}" class="btn btn-link btn-sm w-100 mt-1">Reset all</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- ============ RIGHT: PRODUCTS (only this part scrolls) ============ --}}
            <div class="col-lg sgh-products-col">

                {{-- TOOLBAR: Filters button (mobile), count, sort --}}
                <div class="sgh-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        {{-- Opens the filter panel (mobile/tablet only) --}}
                        <button class="btn btn-outline-primary btn-sm rounded-pill d-lg-none" type="button"
                                data-bs-toggle="offcanvas" data-bs-target="#filterPanel" aria-controls="filterPanel">
                            <i class="bi bi-sliders me-1"></i> Filters
                        </button>
                        <span class="text-secondary small">{{ $products->total() }} product(s)</span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <label for="sort" class="small text-secondary mb-0 d-none d-sm-block">Sort by</label>
                        {{-- form="filterForm" links this dropdown to the filter form --}}
                        <select name="sort" id="sort" form="filterForm" class="form-select form-select-sm w-auto"
                                onchange="this.form.submit()">
                            <option value="newest"     @selected($sort === 'newest')>Newest</option>
                            <option value="price_asc"  @selected($sort === 'price_asc')>Price: low to high</option>
                            <option value="price_desc" @selected($sort === 'price_desc')>Price: high to low</option>
                            <option value="name"       @selected($sort === 'name')>Name A–Z</option>
                        </select>
                    </div>
                </div>

                {{-- ACTIVE FILTER CHIPS: click the x to remove one filter --}}
                @if ($hasMin || $hasMax || request()->boolean('in_stock'))
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @if ($hasMin)
                            <a href="{{ request()->fullUrlWithoutQuery(['min', 'page']) }}" class="sgh-chip">Min {{ request('min') }} <i class="bi bi-x"></i></a>
                        @endif
                        @if ($hasMax)
                            <a href="{{ request()->fullUrlWithoutQuery(['max', 'page']) }}" class="sgh-chip">Max {{ request('max') }} <i class="bi bi-x"></i></a>
                        @endif
                        @if (request()->boolean('in_stock'))
                            <a href="{{ request()->fullUrlWithoutQuery(['in_stock', 'page']) }}" class="sgh-chip">In stock <i class="bi bi-x"></i></a>
                        @endif
                    </div>
                @endif

                @if ($products->isEmpty())
                    {{-- Nothing found --}}
                    <div class="sgh-empty text-center">
                        <i class="bi bi-search fs-1 text-secondary"></i>
                        <h2 class="h5 mt-3">No products found</h2>
                        <p class="text-secondary">Try removing a filter or browsing all products.</p>
                        <a href="{{ url('/shop') }}" class="btn btn-primary rounded-pill px-4">View all products</a>
                    </div>
                @else
                    {{-- 2 per row on phones, 3 on tablets, 4 on laptops, 5 on very wide screens --}}
                    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 row-cols-xxl-5 g-2 g-md-3">
                        @foreach ($products as $product)
                            <div class="col">
                                <x-product-card :product="$product" />
                            </div>
                        @endforeach
                    </div>

                    {{-- Page numbers (appear when there are more than 12 products) --}}
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@endsection