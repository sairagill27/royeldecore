@extends('layouts.app')

@section('title', 'Order ' . $order->order_number . ' | ' . config('app.name'))

@section('content')

@php $currency = config('shop.currency'); @endphp

<div class="container py-4 py-lg-5">

    {{-- Green "Thank you" box: shown ONLY right after the order was placed --}}
    @if (session('placed'))
    <div class="sgh-success text-center mb-4">
        <i class="bi bi-check-circle-fill"></i>
        <h1 class="h3 mt-2 mb-1">Thank you, your order is placed!</h1>
        <p class="mb-0 text-secondary">
            Your order number is <strong>{{ $order->order_number }}</strong>.
            We will contact you on {{ $order->customer_phone }} to confirm delivery.
        </p>
    </div>
    @else
    <h1 class="sgh-section-title mb-1">Order {{ $order->order_number }}</h1>
    <p class="text-secondary mb-4">
        <a href="{{ route('account.orders') }}"><i class="bi bi-arrow-left"></i> Back to my orders</a>
    </p>
    @endif

    <div class="row g-4">

        {{-- ============ LEFT: ITEMS ============ --}}
        <div class="col-lg-8">
            <div class="sgh-card">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h5 mb-0">Items</h2>
                    <span class="sgh-status {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
                </div>

                {{-- table-responsive: the table scrolls inside its box on small phones --}}
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Price</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- These values are the COPY saved at purchase time --}}
                            @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    {{ $item->product_name }}
                                    @if ($item->sku)
                                    <small class="d-block text-secondary">SKU: {{ $item->sku }}</small>
                                    @endif
                                </td>
                                <td class="text-end">{{ $currency }} {{ number_format($item->price) }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end fw-semibold">{{ $currency }} {{ number_format($item->line_total) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ============ RIGHT: TOTALS, ADDRESS, PAYMENT ============ --}}
        <div class="col-lg-4">

            <div class="sgh-card mb-3">
                <h2 class="h5 mb-3">Summary</h2>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Subtotal</span>
                    <span>{{ $currency }} {{ number_format($order->subtotal) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-secondary">Shipping</span>
                    <span>{{ $order->shipping_fee > 0 ? $currency . ' ' . number_format($order->shipping_fee) : 'Free' }}</span>
                </div>
                <div class="d-flex justify-content-between border-top pt-3">
                    <strong>Total</strong>
                    <strong class="fs-5">{{ $currency }} {{ number_format($order->total) }}</strong>
                </div>
            </div>

            <div class="sgh-card mb-3">
                <h2 class="h6 mb-2">Shipping to</h2>
                <p class="small mb-0">
                    {{ $order->customer_name }}<br>
                    {{ $order->shipping_address }}<br>
                    {{ $order->shipping_city }}{{ $order->shipping_postal_code ? ', ' . $order->shipping_postal_code : '' }}<br>
                    {{ $order->shipping_country }}<br>
                    <span class="text-secondary">{{ $order->customer_phone }}</span>
                </p>
            </div>

            <div class="sgh-card">
                <h2 class="h6 mb-2">Payment</h2>
                <p class="small mb-1">Cash on delivery</p>
                <p class="small text-secondary mb-0">
                    Placed on {{ $order->created_at->timezone(config('shop.timezone'))->format('d M Y, h:i A') }}
                </p>
                @if ($order->notes)
                <p class="small mt-2 mb-0"><strong>Your note:</strong> {{ $order->notes }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection