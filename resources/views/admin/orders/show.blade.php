@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number)
@section('heading', 'Order ' . $order->order_number)

@section('content')

@php
$currency = config('shop.currency');
$next = $order->nextStatuses();
@endphp

<p class="mb-3">
    <a href="{{ route('admin.orders.index') }}"><i class="bi bi-arrow-left"></i> Back to orders</a>
</p>

<div class="row g-3">

    {{-- ============ LEFT: ITEMS ============ --}}
    <div class="col-lg-8">
        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h2>Items</h2>
                    <p>Placed on {{ $order->created_at->timezone(config('shop.timezone'))->format('d M Y, h:i A') }}</p>
                </div>
                <span class="sgh-status {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
            </div>

            <div class="table-responsive">
                <table class="table sgh-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Price</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- These values are the copy saved when the order was placed --}}
                        @foreach ($order->items as $item)
                        <tr>
                            <td>
                                {{ $item->product_name }}
                                @if ($item->sku) <small class="d-block text-secondary">SKU: {{ $item->sku }}</small> @endif
                                @unless ($item->product_id) <small class="d-block text-secondary">(product deleted)</small> @endunless
                            </td>
                            <td class="text-end">{{ $currency }} {{ number_format($item->price) }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end fw-semibold">{{ $currency }} {{ number_format($item->line_total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-top mt-3 pt-3 ms-auto" style="max-width: 320px;">
                <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Subtotal</span><span>{{ $currency }} {{ number_format($order->subtotal) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Shipping</span><span>{{ $order->shipping_fee > 0 ? $currency . ' ' . number_format($order->shipping_fee) : 'Free' }}</span></div>
                <div class="d-flex justify-content-between border-top pt-2"><strong>Total</strong><strong class="fs-5">{{ $currency }} {{ number_format($order->total) }}</strong></div>
            </div>
        </div>

        @if ($order->notes)
        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div>
                    <h2>Customer note</h2>
                </div>
            </div>
            {{-- {{ }} escapes the text, so nothing typed by a customer can run as code --}}
            <p class="mb-0">{{ $order->notes }}</p>
        </div>
        @endif
    </div>

    {{-- ============ RIGHT: STATUS, CUSTOMER, PAYMENT ============ --}}
    <div class="col-lg-4">

        {{-- CHANGE STATUS --}}
        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head">
                <div>
                    <h2>Order status</h2>
                </div>
            </div>

            @if (count($next) === 0)
            <p class="small text-secondary mb-0">
                <i class="bi bi-lock"></i>
                This order is <strong>{{ $order->statusLabel() }}</strong>. No further changes are possible.
            </p>
            @else
            {{-- Asks "are you sure?" before cancelling --}}
            <form action="{{ route('admin.orders.status', $order->order_number) }}" method="POST"
                onsubmit="if (this.status.value === 'cancelled') { return confirm('Cancel this order? The stock will be returned.'); }">
                @csrf
                @method('PATCH')

                <label for="status" class="form-label">Move this order to</label>
                <select name="status" id="status" class="form-select mb-3">
                    {{-- Only the allowed next steps are listed --}}
                    @foreach ($next as $status)
                    <option value="{{ $status }}">{{ \App\Models\Order::STATUSES[$status] }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary w-100 rounded-pill">Update status</button>
            </form>

            @if (in_array('cancelled', $next, true))
            <p class="small text-secondary mt-3 mb-0">Cancelling returns the pieces to stock.</p>
            @endif
            @endif

            @if ($order->status === 'refunded')
            <p class="small text-secondary mt-3 mb-0">A refund does not add stock back. Adjust the stock by hand if the items come back.</p>
            @endif
        </div>

        {{-- CUSTOMER --}}
        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head">
                <div>
                    <h2>Customer</h2>
                </div>
            </div>
            <p class="mb-1 fw-semibold">{{ $order->customer_name }}</p>
            <p class="small mb-1"><i class="bi bi-envelope text-secondary"></i> <a href="mailto:{{ $order->customer_email }}">{{ $order->customer_email }}</a></p>
            <p class="small mb-0"><i class="bi bi-telephone text-secondary"></i> <a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a></p>
        </div>

        {{-- SHIPPING --}}
        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head">
                <div>
                    <h2>Shipping address</h2>
                </div>
            </div>
            <p class="small mb-0">
                {{ $order->shipping_address }}<br>
                {{ $order->shipping_city }}{{ $order->shipping_postal_code ? ', ' . $order->shipping_postal_code : '' }}<br>
                {{ $order->shipping_country }}
            </p>
        </div>

        {{-- PAYMENT --}}
        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div>
                    <h2>Payment</h2>
                </div>
            </div>
            <p class="small mb-1">{{ $order->paymentLabel() }}</p>
            <span class="badge {{ $order->payment_status === 'paid' ? 'sgh-badge-ok' : 'sgh-badge-low' }}">{{ ucfirst($order->payment_status) }}</span>
        </div>
    </div>
</div>

@endsection