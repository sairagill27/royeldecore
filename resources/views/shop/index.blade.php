@extends('layouts.app')

@section('title', ($category ? $category->name : 'Shop') . ' | SGHomeTrends')

@section('content')

{{-- ================= BANNER ================= --}}
<section class="sgh-shop-banner">
    {{-- container-fluid = full screen width (not centered in a narrow box) --}}
    <div class="container-fluid px-3 px-lg-4 px-xl-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-2">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/shop') }}">Shop</a></li>
                @if ($category)
                <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
                @endif
            </ol>
        </nav>
        <h1 class="sgh-section-title mb-1">{{ $category ? $category->name : 'Shop Décor' }}</h1>
        <p class="text-secondary mb-0 small">
            {{ $category && $category->description ? $category->description : 'Beautiful pieces for your walls, shelves and every corner of your home.' }}
        </p>
    </div>
</section>

<section class="py-3 py-lg-4">
    <div class="container-fluid px-3 px-lg-4 px-xl-5">
        <div class="row g-3 g-lg-4">

            {{-- ============ LEFT: STICKY FILTER PANEL ============
                 Desktop: fixed 300px panel at the left edge, stays while products scroll.
                 Mobile: slides in from the side (opened by the Filters button). --}}
            <aside class="col-lg-auto sgh-sidebar-col">
                <div class="sgh-sidebar">
                    <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="filterPanel"
                        aria-labelledby="filterPanelLabel">

                        <div class="offcanvas-header border-bottom">
                            <h2 class="h5 mb-0" id="filterPanelLabel">Filters</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                data-bs-target="#filterPanel" aria-label="Close"></button>
                        </div>

                        <div class="offcanvas-body d-block">

                            {{-- CATEGORIES --}}
                            <div class="sgh-filter-box mb-3">
                                <h2 class="sgh-filter-title">Categories</h2>
                                <ul class="list-unstyled mb-0">
                                    <li><a href="{{ url('/shop') }}" class="sgh-filter-link {{ !$category ? 'active' : '' }}">All products</a></li>
                                    @foreach ($categories as $cat)
                                    <li>
                                        <a href="{{ url('/shop/' . $cat->slug) }}"
                                            class="sgh-filter-link {{ $category && $category->id === $cat->id ? 'active' : '' }}">{{ $cat->name }}</a>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>

                            {{-- FILTERS (a normal GET form, so the link can be shared) --}}
                            <form method="GET" action="{{ url()->current() }}" id="filterForm" class="sgh-filter-box">
                                <h2 class="sgh-filter-title">Price</h2>
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <input type="number" name="min" min="0" class="form-control form-control-sm"
                                        placeholder="Min" value="{{ request('min') }}" aria-label="Minimum price">
                                    <span class="text-secondary">–</span>
                                    <input type="number" name="max" min="0" class="form-control form-control-sm"
                                        placeholder="Max" value="{{ request('max') }}" aria-label="Maximum price">
                                </div>

                                <h2 class="sgh-filter-title">Availability</h2>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" name="in_stock"
                                        value="1" id="inStock" {{ request()->boolean('in_stock') ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="inStock">In stock only</label>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 rounded-pill">Apply filters</button>
                                <a href="{{ url()->current() }}" class="btn btn-link btn-sm w-100 mt-1">Reset all</a>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- ============ RIGHT: PRODUCTS (only this part scrolls) ============ --}}
            <div class="col-lg sgh-products-col">

                {{-- TOOLBAR --}}
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
                            <option value="newest" @selected($sort==='newest' )>Newest</option>
                            <option value="price_asc" @selected($sort==='price_asc' )>Price: low to high</option>
                            <option value="price_desc" @selected($sort==='price_desc' )>Price: high to low</option>
                            <option value="name" @selected($sort==='name' )>Name A–Z</option>
                        </select>
                    </div>
                </div>

                {{-- ACTIVE FILTER CHIPS: click the x to remove one --}}
                @php
                $hasMin = request('min') !== null && request('min') !== '';
                $hasMax = request('max') !== null && request('max') !== '';
                @endphp
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

                <div class="mt-4 d-flex justify-content-center">
                    {{ $products->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection