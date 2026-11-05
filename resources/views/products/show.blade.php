@extends('layouts.app')

@php
    $currency = config('shop.currency', 'Rs.');
    $inStock  = $product->stock > 0;
    $url      = route('products.show', $product->slug);
    $image    = $product->image ? asset('storage/' . $product->image) : null;
    $title    = $product->seo_title ?: $product->name;
    $metaDesc = $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 150);
    $priceRaw = number_format((float) $product->price, 2, '.', '');
    $code     = config('shop.currency_code', 'PKR');

    // Delivery charge, read from config/shop.php (the same numbers checkout uses)
    $fee  = (int) config('shop.shipping_fee', 0);
    $over = (int) config('shop.free_shipping_over', 0);

    // WhatsApp: only shown when a valid number is set in .env (CONTACT_WHATSAPP)
    $wa = preg_replace('/\D/', '', (string) config('contact.whatsapp'));
    $wa = (strlen($wa) >= 10 && strlen($wa) <= 15) ? $wa : null;
    $askText   = rawurlencode('Hello, I have a question about ' . $product->name . ': ' . $url);
    $shareUrl  = rawurlencode($url);
    $shareText = rawurlencode($product->name);

    // Data for search engines (printed safely by JsonLd::tag in the head)
    $offer = [
        '@type'         => 'Offer',
        'url'           => $url,
        'priceCurrency' => $code,
        'price'         => $priceRaw,
        'availability'  => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
    ];
    $productLd = [
        '@type'       => 'Product',
        'name'        => $product->name,
        'description' => $metaDesc ?: $product->name,
        'url'         => $url,
        'brand'       => ['@type' => 'Brand', 'name' => config('app.name')],
        'offers'      => $offer,
    ];
    if ($image) {
        $productLd['image'] = [$image];
    }
    if (filled($product->sku)) {
        $productLd['sku'] = $product->sku;
    }
    if ($product->category) {
        $productLd['category'] = $product->category->name;
    }
    // Star ratings are added only when there are real, approved reviews
    if (($ratingCount ?? 0) > 0) {
        $productLd['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $ratingAvg, 'reviewCount' => $ratingCount];
    }

    $crumbs = [['Home', url('/')], ['Shop', url('/shop')]];
    if ($product->category) {
        $crumbs[] = [$product->category->name, url('/shop/' . $product->category->slug)];
    }
    $crumbs[] = [$product->name, $url];

    $breadcrumbLd = [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => collect($crumbs)->map(fn ($c, $i) => [
            '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1],
        ])->values()->all(),
    ];

    $ld = ['@context' => 'https://schema.org', '@graph' => [$productLd, $breadcrumbLd]];
@endphp

@section('title', $title . ' | ' . config('app.name'))
@section('meta_description', $metaDesc)
@section('has_og', '1')

@push('head')
    <link rel="canonical" href="{{ $url }}">

    {{-- Shown when the product link is shared (WhatsApp, Facebook...) --}}
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $metaDesc }}">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    @if ($image)
        <meta property="og:image" content="{{ $image }}">
    @endif
    <meta property="product:price:amount" content="{{ $priceRaw }}">
    <meta property="product:price:currency" content="{{ $code }}">
    <meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">

    {!! \App\Services\JsonLd::tag($ld) !!}
@endpush

@section('content')

{{-- ============ SLIM GREEN STRIP: breadcrumb (same style as the shop and the tools) ============ --}}
<section class="sgh-tool-head sgh-pd-strip">
    @include('tools._leaves')
    <div class="container position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/shop') }}">Shop</a></li>
                @if ($product->category)
                    <li class="breadcrumb-item"><a href="{{ url('/shop/' . $product->category->slug) }}">{{ $product->category->name }}</a></li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
            </ol>
        </nav>
    </div>
</section>

<div class="container sgh-pd-page pb-4 pb-lg-5">

    {{-- ============ THE PRODUCT CARD ============ --}}
    <div class="sgh-pd-card">
        <div class="row g-4 g-lg-5">

            {{-- ---------- LEFT: IMAGE ---------- --}}
            <div class="col-md-6">
                <div class="sgh-pd-media">
                    @if ($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                             width="800" height="800" loading="eager" fetchpriority="high">
                    @else
                        <i class="bi bi-image" aria-hidden="true"></i>
                    @endif

                    @if ($inStock && $product->stock <= 5)
                        <span class="sgh-pd-flag"><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> Only {{ $product->stock }} left</span>
                    @elseif (! $inStock)
                        <span class="sgh-pd-flag is-out">Out of stock</span>
                    @endif
                </div>
            </div>

            {{-- ---------- RIGHT: DETAILS ---------- --}}
            <div class="col-md-6">

                @if ($product->category)
                    <a href="{{ url('/shop/' . $product->category->slug) }}" class="sgh-pd-cat">{{ $product->category->name }}</a>
                @endif

                {{-- {{ }} escapes the text, so nothing in a name can run as code --}}
                <h1 class="sgh-pd-title">{{ $product->name }}</h1>

                @if (($ratingCount ?? 0) > 0)
                    <a href="#reviews" class="sgh-pd-rating">
                        <span class="sgh-stars" role="img" aria-label="Rated {{ $ratingAvg }} out of 5">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $i <= round($ratingAvg) ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                            @endfor
                        </span>
                        <span>{{ $ratingAvg }} &middot; {{ $ratingCount }} {{ \Illuminate\Support\Str::plural('review', $ratingCount) }}</span>
                    </a>
                @endif

                <p class="sgh-pd-price">{{ $currency }} {{ number_format($product->price) }}</p>

                @if ($product->stock > 5)
                    <span class="badge sgh-badge-ok mb-3"><i class="bi bi-check-circle me-1"></i>In stock</span>
                @elseif ($inStock)
                    <span class="badge sgh-badge-low mb-3"><i class="bi bi-exclamation-circle me-1"></i>Only {{ $product->stock }} left</span>
                @else
                    <span class="badge sgh-badge-out mb-3"><i class="bi bi-x-circle me-1"></i>Out of stock</span>
                @endif

                <p class="sgh-pd-lead">{{ \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 200) }}</p>

                {{-- ADD TO CART FORM.
                     @csrf = security token. We send only the product id and quantity;
                     the price is always read from the database, never from the browser. --}}
                <form action="{{ route('cart.add') }}" method="POST" id="cartForm" class="sgh-pd-buy">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    <div class="sgh-qty">
                        <button type="button" id="qtyMinus" aria-label="Decrease quantity" @disabled(!$inStock)>&minus;</button>
                        <input type="number" name="quantity" id="qty" value="1" min="1" max="{{ max($product->stock, 1) }}"
                               aria-label="Quantity" @disabled(!$inStock)>
                        <button type="button" id="qtyPlus" aria-label="Increase quantity" @disabled(!$inStock)>+</button>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5" @disabled(!$inStock)>
                        <i class="bi bi-bag-plus me-2"></i>{{ $inStock ? 'Add to Cart' : 'Out of stock' }}
                    </button>
                </form>

                {{-- Save for later, and ask a question (the WhatsApp button needs CONTACT_WHATSAPP in .env) --}}
                <div class="sgh-pd-actions">
                    <x-wishlist-button :product="$product" variant="text" />

                    @if ($wa)
                        <a href="https://wa.me/{{ $wa }}?text={{ $askText }}" target="_blank" rel="noopener noreferrer" class="sgh-pd-ask">
                            <i class="bi bi-whatsapp" aria-hidden="true"></i> Ask on WhatsApp
                        </a>
                    @endif
                </div>

                {{-- What to expect: only things that are true for this shop --}}
                <ul class="sgh-pd-perks">
                    <li>
                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                        <span><strong>Cash on delivery</strong><small>Pay when your order arrives.</small></span>
                    </li>
                    <li>
                        <i class="bi bi-truck" aria-hidden="true"></i>
                        <span>
                            <strong>Delivery</strong>
                            @if ($fee > 0)
                                <small>Delivery charge {{ $currency }} {{ number_format($fee) }}@if ($over > 0), and no charge on orders over {{ $currency }} {{ number_format($over) }}@endif.</small>
                            @else
                                <small>The delivery charge is shown at checkout.</small>
                            @endif
                        </span>
                    </li>
                    <li>
                        <i class="bi bi-box-seam" aria-hidden="true"></i>
                        <span><strong>Track your order</strong><small>Follow its status in My Orders after you sign in.</small></span>
                    </li>
                </ul>

                {{-- Share: plain links, no third-party scripts --}}
                <div class="sgh-pd-share">
                    <strong>Share</strong>
                    <a href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook"><i class="bi bi-facebook"></i></a>
                    <button type="button" id="copyLink" data-url="{{ $url }}" aria-label="Copy link"><i class="bi bi-link-45deg"></i> <span>Copy link</span></button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ DESCRIPTION AND DETAILS ============ --}}
    <div class="row g-4 mt-1">
        <div class="col-lg-8">
            <div class="sgh-pd-box h-100">
                <h2 class="h4 mb-3">Description</h2>
                {{-- e() blocks any HTML, nl2br keeps line breaks (safe) --}}
                <div class="sgh-pd-text">{!! nl2br(e((string) $product->description)) !!}</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="sgh-pd-box h-100">
                <h2 class="h4 mb-3">Product details</h2>
                <dl class="sgh-pd-specs mb-0">
                    @if ($product->sku)
                        <div><dt>SKU</dt><dd>{{ $product->sku }}</dd></div>
                    @endif
                    @if ($product->category)
                        <div><dt>Category</dt><dd>{{ $product->category->name }}</dd></div>
                    @endif
                    <div><dt>Availability</dt><dd>{{ $inStock ? 'In stock' : 'Out of stock' }}</dd></div>
                    <div><dt>Payment</dt><dd>Cash on delivery</dd></div>
                </dl>
            </div>
        </div>
    </div>
</div>

{{-- ============ CUSTOMER REVIEWS ============ --}}
@include('products._reviews', ['product' => $product])

{{-- ============ RELATED PRODUCTS ============ --}}
@if ($related->isNotEmpty())
    <section class="sgh-section pt-0 sgh-pd-related">
        <div class="container">
            <h2 class="h4 mb-4">You may also like</h2>
            <div class="row row-cols-2 row-cols-md-4 g-2 g-md-3">
                @foreach ($related as $item)
                    <div class="col">
                        <x-product-card :product="$item" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ============ STICKY ADD-TO-CART BAR (phones only) ============
     The button belongs to the form above (form="cartForm"), so it sends the same quantity. --}}
<div class="sgh-pd-sticky">
    <div class="sgh-pd-sticky-info">
        <small>{{ \Illuminate\Support\Str::limit($product->name, 30) }}</small>
        <strong>{{ $currency }} {{ number_format($product->price) }}</strong>
    </div>
    <button type="submit" form="cartForm" class="btn btn-primary rounded-pill px-4" @disabled(!$inStock)>
        {{ $inStock ? 'Add to Cart' : 'Out of stock' }}
    </button>
</div>

{{-- The + and minus buttons change the quantity (never below 1 or above stock). Copy link button. --}}
<script>
    (function () {
        var input = document.getElementById('qty');
        if (input) {
            var max = parseInt(input.max, 10) || 1;
            var set = function (v) { input.value = Math.min(max, Math.max(1, v)); };
            document.getElementById('qtyMinus').addEventListener('click', function () { set((parseInt(input.value, 10) || 1) - 1); });
            document.getElementById('qtyPlus').addEventListener('click', function () { set((parseInt(input.value, 10) || 1) + 1); });
            input.addEventListener('change', function () { set(parseInt(input.value, 10) || 1); });
        }

        var copy = document.getElementById('copyLink');
        if (copy) {
            copy.addEventListener('click', function () {
                var label = copy.querySelector('span');
                var done = function () { label.textContent = 'Copied!'; setTimeout(function () { label.textContent = 'Copy link'; }, 2000); };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(copy.dataset.url).then(done);
                } else {
                    window.prompt('Copy this link:', copy.dataset.url);   // older browsers
                }
            });
        }
    })();
</script>

@endsection