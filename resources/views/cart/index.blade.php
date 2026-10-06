@extends('layouts.app')

@section('title', 'Your Cart | ' . config('app.name'))

@section('content')

@php $currency = 'Rs.'; @endphp

<div class="container py-4 py-lg-5">

    <h1 class="sgh-section-title mb-4">Your Cart</h1>

    @if (count($items) === 0)

    {{-- EMPTY CART --}}
    <div class="sgh-empty text-center">
        <i class="bi bi-bag fs-1 text-secondary"></i>
        <h2 class="h5 mt-3">Your cart is empty</h2>
        <p class="text-secondary">Find something beautiful for your home.</p>
        <a href="{{ url('/shop') }}" class="btn btn-primary rounded-pill px-4">Continue shopping</a>
    </div>

    @else
    <div class="row g-4">

        {{-- ============ LEFT: CART ITEMS ============ --}}
        <div class="col-lg-8">

            @foreach ($items as $item)
            @php $product = $item['product']; @endphp

            <div class="sgh-cart-item">

                {{-- Small image (placeholder until photos are added) --}}
                <a href="{{ url('/products/' . $product->slug) }}" class="sgh-cart-thumb" aria-label="{{ $product->name }}">
                    @if ($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                        width="96" height="96" loading="lazy">
                    @else
                    <i class="bi bi-image" aria-hidden="true"></i>
                    @endif
                </a>

                <div class="flex-grow-1">
                    <small class="text-secondary">{{ $product->category?->name }}</small>
                    <h2 class="h6 mb-1">
                        <a href="{{ url('/products/' . $product->slug) }}" class="text-decoration-none text-reset">{{ $product->name }}</a>
                    </h2>
                    <p class="mb-2 text-secondary small">{{ $currency }} {{ number_format($product->price) }} each</p>

                    <div class="d-flex flex-wrap align-items-center gap-3">

                        {{-- CHANGE QUANTITY (PATCH needs @method; @csrf protects the form) --}}
                        <form action="{{ route('cart.update', $product) }}" method="POST" class="d-flex gap-2">
                            @csrf
                            @method('PATCH')
                            <label for="qty-{{ $product->id }}" class="visually-hidden">Quantity for {{ $product->name }}</label>
                            <input type="number" id="qty-{{ $product->id }}" name="quantity"
                                value="{{ $item['quantity'] }}" min="1" max="{{ $product->stock }}"
                                class="form-control form-control-sm sgh-cart-qty">
                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill">Update</button>
                        </form>

                        {{-- REMOVE --}}
                        <form action="{{ route('cart.remove', $product) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link btn-sm text-danger p-0">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        </form>
                    </div>

                    @if ($item['quantity'] >= $product->stock)
                    <small class="text-warning-emphasis d-block mt-2">Maximum available quantity in your cart.</small>
                    @endif
                </div>

                {{-- Line total --}}
                <div class="sgh-cart-line-total">
                    {{ $currency }} {{ number_format($item['line_total']) }}
                </div>
            </div>
            @endforeach

            <div class="d-flex flex-wrap justify-content-between gap-2 mt-3">
                <a href="{{ url('/shop') }}"><i class="bi bi-arrow-left"></i> Continue shopping</a>

                <form action="{{ route('cart.clear') }}" method="POST"
                    onsubmit="return confirm('Remove all items from your cart?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-link btn-sm text-secondary p-0">Empty cart</button>
                </form>
            </div>
        </div>

        {{-- ============ RIGHT: ORDER SUMMARY ============ --}}
        <div class="col-lg-4">
            <div class="sgh-summary">
                <h2 class="h5 mb-3">Order summary</h2>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Subtotal</span>
                    <strong>{{ $currency }} {{ number_format($subtotal) }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-secondary">Shipping</span>
                    <span class="text-secondary small">Calculated at checkout</span>
                </div>

                <div class="d-flex justify-content-between border-top pt-3 mb-4">
                    <strong>Total</strong>
                    <strong class="fs-5">{{ $currency }} {{ number_format($subtotal) }}</strong>
                </div>

                {{-- The checkout page is built in the next step --}}
                <a href="{{ url('/checkout') }}" class="btn btn-primary btn-lg rounded-pill w-100">
                    Proceed to checkout
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection