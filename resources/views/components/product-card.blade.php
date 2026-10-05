{{-- COMPACT PRODUCT CARD: used on the homepage and shop.
     Usage: <x-product-card :product="$product" /> --}}
@props(['product'])

@php $currency = 'Rs.'; @endphp

<div class="card sgh-product-card h-100">

    {{-- Square image. Without a photo, a soft placeholder shows. --}}
    <a href="{{ url('/products/' . $product->slug) }}" class="sgh-product-media" aria-label="{{ $product->name }}">
        @if ($product->image)
        {{-- loading=lazy: loads only when scrolled near (faster page).
                 width/height: stops the page jumping while the image loads. --}}
        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
            width="400" height="400" loading="lazy" decoding="async">
        @else
        <i class="bi bi-image" aria-hidden="true"></i>
        @endif
    </a>

    <div class="card-body d-flex flex-column">
        <small class="sgh-product-cat">{{ $product->category?->name }}</small>

        {{-- {{ }} escapes HTML automatically (XSS protection) --}}
        <h3 class="sgh-product-name">
            <a href="{{ url('/products/' . $product->slug) }}" class="text-decoration-none text-reset">{{ $product->name }}</a>
        </h3>

        <p class="sgh-price">{{ $currency }} {{ number_format($product->price) }}</p>

        @if ($product->stock > 5)
        <span class="badge sgh-badge-ok align-self-start">In stock</span>
        @elseif ($product->stock > 0)
        <span class="badge sgh-badge-low align-self-start">Only {{ $product->stock }} left</span>
        @else
        <span class="badge sgh-badge-out align-self-start">Out of stock</span>
        @endif

        {{-- mt-auto keeps the button at the bottom so all cards line up --}}
        <a href="{{ url('/products/' . $product->slug) }}"
            class="btn btn-outline-primary btn-sm rounded-pill mt-auto">View product</a>
    </div>
</div>