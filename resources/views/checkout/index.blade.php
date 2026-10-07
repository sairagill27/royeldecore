@extends('layouts.app')

@section('title', 'Checkout | ' . config('app.name'))

@section('content')

@php $currency = config('shop.currency'); @endphp

<div class="container py-4 py-lg-5">

    {{-- Progress: Cart > Checkout > Done --}}
    <ol class="sgh-steps" aria-label="Checkout progress">
        <li class="done"><span>1</span> Cart</li>
        <li class="active"><span>2</span> Checkout</li>
        <li><span>3</span> Order placed</li>
    </ol>

    <h1 class="sgh-section-title mb-4">Checkout</h1>

    <div class="row g-4">

        {{-- ============ LEFT: DETAILS FORM ============ --}}
        <div class="col-lg-7">
            {{-- id="checkoutForm": the Place order button on the right is linked to this form --}}
            <form action="{{ route('checkout.store') }}" method="POST" id="checkoutForm" novalidate>
                @csrf

                {{-- 1. CONTACT --}}
                <div class="sgh-card mb-3">
                    <h2 class="sgh-card-title"><span>1</span> Contact details</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="customer_name" class="form-label">Full name</label>
                            <input type="text" id="customer_name" name="customer_name"
                                value="{{ old('customer_name', auth()->user()->name) }}"
                                class="form-control @error('customer_name') is-invalid @enderror"
                                autocomplete="name" required>
                            @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="customer_email" class="form-label">Email</label>
                            <input type="email" id="customer_email" name="customer_email"
                                value="{{ old('customer_email', auth()->user()->email) }}"
                                class="form-control @error('customer_email') is-invalid @enderror"
                                autocomplete="email" required>
                            @error('customer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="customer_phone" class="form-label">Phone</label>
                            <input type="tel" id="customer_phone" name="customer_phone"
                                value="{{ old('customer_phone') }}" placeholder="03XX XXXXXXX"
                                class="form-control @error('customer_phone') is-invalid @enderror"
                                autocomplete="tel" required>
                            @error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- 2. SHIPPING --}}
                <div class="sgh-card mb-3">
                    <h2 class="sgh-card-title"><span>2</span> Shipping address</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="shipping_address" class="form-label">Street address</label>
                            <input type="text" id="shipping_address" name="shipping_address"
                                value="{{ old('shipping_address') }}" placeholder="House no, street, area"
                                class="form-control @error('shipping_address') is-invalid @enderror"
                                autocomplete="street-address" required>
                            @error('shipping_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="shipping_city" class="form-label">City</label>
                            <input type="text" id="shipping_city" name="shipping_city"
                                value="{{ old('shipping_city') }}"
                                class="form-control @error('shipping_city') is-invalid @enderror"
                                autocomplete="address-level2" required>
                            @error('shipping_city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="shipping_postal_code" class="form-label">Postal code <span class="text-secondary">(optional)</span></label>
                            <input type="text" id="shipping_postal_code" name="shipping_postal_code"
                                value="{{ old('shipping_postal_code') }}"
                                class="form-control @error('shipping_postal_code') is-invalid @enderror"
                                autocomplete="postal-code">
                            @error('shipping_postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="shipping_country" class="form-label">Country</label>
                            <input type="text" id="shipping_country" name="shipping_country"
                                value="{{ old('shipping_country', 'Pakistan') }}"
                                class="form-control @error('shipping_country') is-invalid @enderror"
                                autocomplete="country-name" required>
                            @error('shipping_country') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- 3. PAYMENT --}}
                <div class="sgh-card mb-3">
                    <h2 class="sgh-card-title"><span>3</span> Payment</h2>
                    {{-- Only cash on delivery for now. More methods are added later. --}}
                    <div class="sgh-pay-option">
                        <i class="bi bi-cash-coin"></i>
                        <div>
                            <strong class="d-block">Cash on delivery</strong>
                            <small class="text-secondary">Pay with cash when your order arrives.</small>
                        </div>
                        <i class="bi bi-check-circle-fill ms-auto"></i>
                    </div>
                </div>

                {{-- 4. NOTES --}}
                <div class="sgh-card">
                    <h2 class="sgh-card-title"><span>4</span> Order notes <small class="text-secondary fw-normal">(optional)</small></h2>
                    <label for="notes" class="visually-hidden">Order notes</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="500"
                        class="form-control @error('notes') is-invalid @enderror"
                        placeholder="Anything we should know about your delivery?">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </form>
        </div>

        {{-- ============ RIGHT: ORDER SUMMARY ============ --}}
        <div class="col-lg-5 col-xl-4 offset-xl-1">
            <div class="sgh-summary">
                <h2 class="h5 mb-3">Your order</h2>

                @foreach ($items as $item)
                <div class="sgh-mini-item">
                    <div class="sgh-mini-thumb">
                        @if ($item['product']->image)
                        <img src="{{ asset('storage/' . $item['product']->image) }}" alt="" width="52" height="52" loading="lazy">
                        @else
                        <i class="bi bi-image" aria-hidden="true"></i>
                        @endif
                        <span class="sgh-mini-qty">{{ $item['quantity'] }}</span>
                    </div>
                    <span class="flex-grow-1 small">{{ $item['product']->name }}</span>
                    <span class="small fw-semibold">{{ $currency }} {{ number_format($item['line_total']) }}</span>
                </div>
                @endforeach

                <div class="border-top mt-3 pt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Subtotal</span>
                        <span>{{ $currency }} {{ number_format($subtotal) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-secondary">Shipping</span>
                        <span>{{ $shipping > 0 ? $currency . ' ' . number_format($shipping) : 'Free' }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-3 mb-4">
                        <strong>Total</strong>
                        <strong class="fs-5">{{ $currency }} {{ number_format($total) }}</strong>
                    </div>
                </div>

                {{-- form="checkoutForm" links this button to the form on the left --}}
                <button type="submit" form="checkoutForm" id="placeOrderBtn"
                    class="btn btn-primary btn-lg rounded-pill w-100">
                    <i class="bi bi-lock me-1"></i> Place order
                </button>
                <p class="text-center small text-secondary mt-3 mb-0">
                    <a href="{{ route('cart.index') }}"><i class="bi bi-arrow-left"></i> Back to cart</a>
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Stops double clicks: the button turns off after the first click --}}
<script>
    document.getElementById('checkoutForm').addEventListener('submit', function() {
        var b = document.getElementById('placeOrderBtn');
        b.disabled = true;
        b.textContent = 'Placing your order…';
    });
</script>

@endsection