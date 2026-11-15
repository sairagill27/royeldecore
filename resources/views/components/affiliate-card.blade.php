{{-- An affiliate product card. variant="wide" sits inside an article, variant="tile" is used on the Recommended page
     and under articles. Everything is printed with {{ }}, so it is always plain text.
     The link carries rel="sponsored nofollow noopener", and says it opens a new tab for screen readers. --}}
@props(['link', 'variant' => 'wide'])

@php
    $rel = config('affiliate.rel', 'sponsored nofollow noopener');
@endphp

@if ($variant === 'tile')
    <div class="card sgh-affiliate sgh-lift h-100 border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="ratio ratio-4x3 bg-success-subtle">
            @if ($link->image)
                <img src="{{ asset('storage/' . $link->image) }}" alt="{{ $link->name }}" class="object-fit-cover" width="640" height="480" loading="lazy">
            @else
                <div class="d-flex align-items-center justify-content-center fs-1 text-success"><i class="bi bi-bag-heart" aria-hidden="true"></i></div>
            @endif
        </div>
        <div class="card-body d-flex flex-column p-4">
            <div class="d-flex flex-wrap gap-1 mb-2">
                <span class="badge rounded-pill text-bg-light border">Affiliate link &middot; {{ $link->merchant }}</span>
                @if ($link->is_featured)
                    <span class="badge rounded-pill text-bg-warning"><i class="bi bi-star-fill me-1" aria-hidden="true"></i>Featured</span>
                @endif
            </div>
            <h3 class="card-title h5">{{ $link->name }}</h3>
            @if ($link->description)
                <p class="card-text small text-secondary">{{ $link->description }}</p>
            @endif
            <a href="{{ $link->href() }}" target="_blank" rel="{{ $rel }}" class="btn sgh-btn-dark rounded-pill px-4 mt-auto align-self-start">
                View on {{ $link->merchant }} <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
                <span class="visually-hidden">(affiliate link, opens in a new tab)</span>
            </a>
            <p class="small text-secondary mt-2 mb-0">Check the current price and details on {{ $link->merchant }}.</p>
        </div>
    </div>
@else
    <div class="card sgh-affiliate border-0 shadow-sm rounded-4 overflow-hidden my-4">
        <div class="row g-0 align-items-center">
            <div class="col-sm-4">
                <div class="ratio ratio-4x3 bg-success-subtle">
                    @if ($link->image)
                        <img src="{{ asset('storage/' . $link->image) }}" alt="{{ $link->name }}" class="object-fit-cover" width="640" height="480" loading="lazy">
                    @else
                        <div class="d-flex align-items-center justify-content-center fs-1 text-success"><i class="bi bi-bag-heart" aria-hidden="true"></i></div>
                    @endif
                </div>
            </div>
            <div class="col-sm-8">
                <div class="card-body p-4">
                    <span class="badge rounded-pill text-bg-light border mb-2">Affiliate link &middot; {{ $link->merchant }}</span>
                    <h3 class="card-title h5">{{ $link->name }}</h3>
                    @if ($link->description)
                        <p class="card-text small text-secondary">{{ $link->description }}</p>
                    @endif
                    <a href="{{ $link->href() }}" target="_blank" rel="{{ $rel }}" class="btn sgh-btn-dark rounded-pill px-4">
                        View on {{ $link->merchant }} <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
                        <span class="visually-hidden">(affiliate link, opens in a new tab)</span>
                    </a>
                    <p class="small text-secondary mt-2 mb-0">Check the current price and details on {{ $link->merchant }}.</p>
                </div>
            </div>
        </div>
    </div>
@endif