{{-- PRODUCT CARD: reused on the homepage, shop and related products.
     Use it like: <x-product-card :product="$product" /> --}}
@props(['product'])

@php
// Change the currency symbol here once. Used in every card.
$currency = 'Rs.';
@endphp

<div class="card sgh-product-card h-100">

    {{-- Image area. No photos yet, so a soft placeholder shows instead. --}}
    <a href="{{ url('/products/' . $product->slug) }}" class="sgh-product-media" aria-label="{{ $product->name }}">
        @if ($product->image)
        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy">
        @else
        <i class="bi bi-image" aria-hidden="true"></i>
        @endif
    </a>

    <div class="card-body d-flex flex-column">

        {{-- Category name --}}
        <small class="text-secondary">{{ $product->category?->name }}</small>

        {{-- Product name. {{ }} escapes HTML automatically (XSS protection). --}}
        <h3 class="h6 sgh-product-name my-1">
            <a href="{{ url('/products/' . $product->slug) }}" class="text-decoration-none text-reset">
                {{ $product->name }}
            </a>
        </h3>

        {{-- Price --}}
        <p class="fw-semibold mb-2">{{ $currency }} {{ number_format($product->price) }}</p>

        {{-- Stock status --}}
        @if ($product->stock > 5)
        <span class="badge sgh-badge-ok mb-3 align-self-start">In stock</span>
        @elseif ($product->stock > 0)
        <span class="badge sgh-badge-low mb-3 align-self-start">Only {{ $product->stock }} left</span>
        @else
        <span class="badge sgh-badge-out mb-3 align-self-start">Out of stock</span>
        @endif

        {{-- mt-auto pushes the button to the bottom so all cards line up.
             Add to Cart comes when we build the cart; for now it opens the product. --}}
        <a href="{{ url('/products/' . $product->slug) }}"
            class="btn btn-outline-primary rounded-pill mt-auto">View product</a>
    </div>
</div>