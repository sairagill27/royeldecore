@extends('layouts.admin')

@section('title', 'Orders')
@section('heading', 'Orders')

@section('content')

@php
$currency = config('shop.currency');
$current = request('status');
@endphp

<div class="sgh-panel">

    {{-- ============ STATUS TABS (with counters) ============ --}}
    <nav class="sgh-pills mb-3" aria-label="Filter orders by status">
        <a href="{{ route('admin.orders.index', array_filter(['q' => request('q')])) }}"
            class="sgh-pill {{ $current ? '' : 'active' }}">All ({{ $counts->sum() }})</a>

        @foreach (\App\Models\Order::STATUSES as $key => $label)
        <a href="{{ route('admin.orders.index', array_filter(['status' => $key, 'q' => request('q')])) }}"
            class="sgh-pill {{ $current === $key ? 'active' : '' }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
        @endforeach
    </nav>

    {{-- ============ SEARCH ============ --}}
    <form action="{{ route('admin.orders.index') }}" method="GET" class="sgh-admin-filters mb-3" role="search">
        @if ($current) <input type="hidden" name="status" value="{{ $current }}"> @endif

        <label for="q" class="visually-hidden">Search orders</label>
        <input type="search" id="q" name="q" value="{{ request('q') }}"
            class="form-control form-control-sm" placeholder="Order number, name, email or phone…">
        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Search</button>
        @if (request('q') || $current)
        <a href="{{ route('admin.orders.index') }}" class="btn btn-link btn-sm">Reset</a>
        @endif
    </form>

    @if ($orders->isEmpty())

    <p class="sgh-panel-empty"><i class="bi bi-receipt"></i> No orders found.</p>

    @else
    <div class="table-responsive">
        <table class="table sgh-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th class="text-center">Items</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th class="text-end">Total</th>
                    <th class="text-end"><span class="visually-hidden">Open</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                <tr>
                    <td>
                        <a href="{{ route('admin.orders.show', $order->order_number) }}" class="fw-semibold text-decoration-none">
                            {{ $order->order_number }}
                        </a>
                        <small class="d-block text-secondary">
                            {{ $order->created_at->timezone(config('shop.timezone'))->format('d M Y, h:i A') }}
                        </small>
                    </td>
                    <td>
                        {{ $order->customer_name }}
                        <small class="d-block text-secondary">{{ $order->shipping_city }}</small>
                    </td>
                    <td class="text-center">{{ $order->items_count }}</td>
                    <td><span class="sgh-status {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span></td>
                    <td>
                        <small class="d-block">{{ $order->paymentLabel() }}</small>
                        <small class="text-secondary">{{ ucfirst($order->payment_status) }}</small>
                    </td>
                    <td class="text-end fw-semibold">{{ $currency }} {{ number_format($order->total) }}</td>
                    <td>
                        <div class="sgh-row-actions">
                            <a href="{{ route('admin.orders.show', $order->order_number) }}"
                                class="sgh-icon-action" title="Open" aria-label="Open order {{ $order->order_number }}">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $orders->links() }}
    </div>
    @endif
</div>

@endsection