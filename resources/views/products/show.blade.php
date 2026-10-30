@extends('layouts.app')

{{-- SEO: the page title and description come from the product --}}
@section('title', ($product->seo_title ?: $product->name) . ' | ' . config('app.name'))
@section('meta_description', $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($product->description), 150))

@section('content')

@php
$currency = 'Rs.';
$inStock = $product->stock > 0;
@endphp

<div class="container py-4 py-lg-5">

    {{-- Breadcrumb: Home > Shop > Category > Product --}}
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small mb-4">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ url('/shop') }}">Shop</a></li>
            @if ($product->category)
            <li class="breadcrumb-item">
                <a href="{{ url('/shop/' . $product->category->slug) }}">{{ $product->category->name }}</a>
            </li>
            @endif
            <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="row g-4 g-lg-5">

        {{-- ============ LEFT: IMAGE ============ --}}
        <div class="col-md-6">
            <div class="sgh-detail-media">
                @if ($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                    width="800" height="800" loading="eager" fetchpriority="high">
                @else
                <i class="bi bi-image" aria-hidden="true"></i>
                @endif
            </div>
        </div>

        {{-- ============ RIGHT: DETAILS ============ --}}
        <div class="col-md-6">

            @if ($product->category)
            <a href="{{ url('/shop/' . $product->category->slug) }}" class="sgh-eyebrow text-decoration-none">
                {{ $product->category->name }}
            </a>
            @endif

            {{-- {{ }} escapes HTML automatically (XSS protection) --}}
            <h1 class="sgh-detail-title mt-2 mb-3">{{ $product->name }}</h1>

            <p class="sgh-detail-price mb-3">{{ $currency }} {{ number_format($product->price) }}</p>

            @if ($product->stock > 5)
            <span class="badge sgh-badge-ok mb-3"><i class="bi bi-check-circle me-1"></i>In stock</span>
            @elseif ($inStock)
            <span class="badge sgh-badge-low mb-3"><i class="bi bi-exclamation-circle me-1"></i>Only {{ $product->stock }} left</span>
            @else
            <span class="badge sgh-badge-out mb-3"><i class="bi bi-x-circle me-1"></i>Out of stock</span>
            @endif

            <p class="text-secondary">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 200) }}</p>

            {{-- ADD TO CART FORM.
                 @csrf = security token. We send only the product id and quantity;
                 the price is always read from the database, never from the browser. --}}
            <form action="{{ route('cart.add') }}" method="POST" class="d-flex flex-wrap align-items-center gap-3 my-4">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                <div class="sgh-qty">
                    <button type="button" id="qtyMinus" aria-label="Decrease quantity" @disabled(!$inStock)>−</button>
                    <input type="number" name="quantity" id="qty" value="1" min="1" max="{{ max($product->stock, 1) }}"
                        aria-label="Quantity" @disabled(!$inStock)>
                    <button type="button" id="qtyPlus" aria-label="Increase quantity" @disabled(!$inStock)>+</button>
                </div>

                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5" @disabled(!$inStock)>
                    <i class="bi bi-bag-plus me-2"></i>{{ $inStock ? 'Add to Cart' : 'Out of stock' }}
                </button>
            </form>

    {{-- Save to wishlist --}}
    <x-wishlist-button :product="$product" variant="text" />

            <ul class="list-unstyled small text-secondary border-top pt-3 mb-0">
                @if ($product->sku)
                <li class="mb-1"><strong class="text-body">SKU:</strong> {{ $product->sku }}</li>
                @endif
                @if ($product->category)
                <li class="mb-1"><strong class="text-body">Category:</strong> {{ $product->category->name }}</li>
                @endif
                <li><strong class="text-body">Availability:</strong> {{ $inStock ? 'In stock' : 'Out of stock' }}</li>
            </ul>
        </div>
    </div>

    {{-- ============ DESCRIPTION ============ --}}
    <div class="sgh-detail-section">
        <h2 class="h4 mb-3">Description</h2>
        {{-- e() blocks any HTML, nl2br keeps line breaks (safe) --}}
        <div class="text-secondary">{!! nl2br(e($product->description)) !!}</div>
    </div>

    {{-- ============ RELATED PRODUCTS ============ --}}
    @if ($related->isNotEmpty())
    <div class="sgh-detail-section">
        <h2 class="h4 mb-4">You may also like</h2>
        <div class="row row-cols-2 row-cols-md-4 g-2 g-md-3">
            @foreach ($related as $item)
            <div class="col">
                <x-product-card :product="$item" />
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- The + and − buttons change the quantity (never below 1 or above stock) --}}
<script>
    (function() {
        var input = document.getElementById('qty');
        if (!input) return;
        var max = parseInt(input.max, 10) || 1;

        function set(v) {
            input.value = Math.min(max, Math.max(1, v));
        }
        document.getElementById('qtyMinus').addEventListener('click', function() {
            set((parseInt(input.value, 10) || 1) - 1);
        });
        document.getElementById('qtyPlus').addEventListener('click', function() {
            set((parseInt(input.value, 10) || 1) + 1);
        });
        input.addEventListener('change', function() {
            set(parseInt(input.value, 10) || 1);
        });
    })();
</script>

@endsection