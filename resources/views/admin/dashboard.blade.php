@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')

@php
    $currency = config('shop.currency');
    $hour     = now(config('shop.timezone'))->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
@endphp

{{-- ============ WELCOME ============ --}}
<div class="sgh-hello">
    <div>
        <h2>{{ $greeting }}, {{ $firstName }}</h2>
        <p>Here is what is happening in your store today.</p>
    </div>
    <span class="sgh-hello-date">
        <i class="bi bi-calendar3"></i>
        {{ now(config('shop.timezone'))->format('l, d F Y') }}
    </span>
</div>

{{-- ============ STATISTIC CARDS (each has its own accent colour) ============ --}}
<div class="row g-3 mb-4">

    <div class="col-6 col-xl-3">
        <div class="sgh-stat sgh-stat--sage">
            <span class="sgh-stat-icon"><i class="bi bi-cash-stack"></i></span>
            <div>
                <small>Total revenue</small>
                <strong>{{ $currency }} {{ number_format($stats['revenue']) }}</strong>
                <em>Excludes cancelled orders</em>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="sgh-stat sgh-stat--gold">
            <span class="sgh-stat-icon"><i class="bi bi-receipt"></i></span>
            <div>
                <small>Orders</small>
                <strong>{{ number_format($stats['orders']) }}</strong>
                <em>{{ $stats['pending'] }} pending</em>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="sgh-stat sgh-stat--forest">
            <span class="sgh-stat-icon"><i class="bi bi-box-seam"></i></span>
            <div>
                <small>Products</small>
                <strong>{{ number_format($stats['products']) }}</strong>
                <em>In your catalogue</em>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="sgh-stat sgh-stat--clay">
            <span class="sgh-stat-icon"><i class="bi bi-people"></i></span>
            <div>
                <small>Customers</small>
                <strong>{{ number_format($stats['customers']) }}</strong>
                <em>Registered accounts</em>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">

    {{-- ============ SALES CHART (last 7 days) ============ --}}
    <div class="col-lg-8">
        <div class="sgh-panel h-100">
            <div class="sgh-panel-head">
                <div>
                    <h2>Sales, last 7 days</h2>
                    <p>{{ $currency }} {{ number_format($weekTotal) }} this week</p>
                </div>
            </div>

            @if ($weekTotal == 0)
                <p class="sgh-panel-empty"><i class="bi bi-graph-up"></i> No sales in the last 7 days yet.</p>
            @else
                <div class="sgh-chart" role="img" aria-label="Bar chart of sales for the last 7 days">
                    @foreach ($sales as $day)
                        @php $height = $day['total'] > 0 ? max(6, round($day['total'] / $maxSale * 100)) : 3; @endphp
                        <div class="sgh-chart-col" title="{{ $day['date'] }}: {{ $currency }} {{ number_format($day['total']) }}">
                            <span class="sgh-chart-value">{{ $day['total'] > 0 ? number_format($day['total']) : '' }}</span>
                            <div class="sgh-chart-bar {{ $day['total'] > 0 ? '' : 'is-empty' }}" style="height: {{ $height }}%"></div>
                            <span class="sgh-chart-label">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ============ ORDERS BY STATUS ============ --}}
    <div class="col-lg-4">
        <div class="sgh-panel h-100">
            <div class="sgh-panel-head"><div><h2>Orders by status</h2></div></div>

            @if ($statusCounts->isEmpty())
                <p class="sgh-panel-empty"><i class="bi bi-receipt"></i> No orders yet.</p>
            @else
                <ul class="sgh-status-list">
                    @foreach (\App\Models\Order::STATUSES as $key => $label)
                        @if (($statusCounts[$key] ?? 0) > 0)
                            <li>
                                <span class="sgh-status sgh-status-{{ $key }}">{{ $label }}</span>
                                <strong>{{ $statusCounts[$key] }}</strong>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- ============ RECENT ORDERS ============ --}}
    <div class="col-xl-8">
        <div class="sgh-panel h-100">
            <div class="sgh-panel-head"><div><h2>Recent orders</h2></div></div>

            @if ($recentOrders->isEmpty())
                <p class="sgh-panel-empty"><i class="bi bi-inbox"></i> New orders will appear here.</p>
            @else
                <div class="table-responsive">
                    <table class="table sgh-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentOrders as $order)
                                <tr>
                                    <td>
                                        <strong class="d-block">{{ $order->order_number }}</strong>
                                        <small class="text-secondary">{{ $order->created_at->timezone(config('shop.timezone'))->format('d M Y, h:i A') }}</small>
                                    </td>
                                    <td>{{ $order->customer_name }}</td>
                                    <td><span class="sgh-status {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span></td>
                                    <td class="text-end fw-semibold">{{ $currency }} {{ number_format($order->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ============ LOW STOCK ============ --}}
    <div class="col-xl-4">
        <div class="sgh-panel h-100">
            <div class="sgh-panel-head"><div><h2>Low stock</h2><p>5 pieces or fewer</p></div></div>

            @if ($lowStock->isEmpty())
                <p class="sgh-panel-empty"><i class="bi bi-check-circle"></i> All products have enough stock.</p>
            @else
                <ul class="sgh-stock-list">
                    @foreach ($lowStock as $product)
                        <li>
                            <span>{{ $product->name }}</span>
                            <span class="badge {{ $product->stock === 0 ? 'sgh-badge-out' : 'sgh-badge-low' }}">
                                {{ $product->stock === 0 ? 'Sold out' : $product->stock . ' left' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

@endsection