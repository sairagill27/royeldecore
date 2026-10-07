@extends('layouts.app')

@section('title', 'My Orders | ' . config('app.name'))

@section('content')

@php $currency = config('shop.currency'); @endphp

<div class="container py-4 py-lg-5">

    <h1 class="sgh-section-title mb-1">My orders</h1>
    <p class="text-secondary mb-4"><a href="{{ route('account') }}"><i class="bi bi-arrow-left"></i> Back to my account</a></p>

    @if ($orders->isEmpty())
    <div class="sgh-empty text-center">
        <i class="bi bi-box-seam fs-1 text-secondary"></i>
        <h2 class="h5 mt-3">No orders yet</h2>
        <p class="text-secondary">When you place an order, it will appear here.</p>
        <a href="{{ url('/shop') }}" class="btn btn-primary rounded-pill px-4">Start shopping</a>
    </div>
    @else
    @foreach ($orders as $order)
    <a href="{{ route('account.orders.show', $order->order_number) }}" class="sgh-order-row">
        <div>
            <strong class="d-block">{{ $order->order_number }}</strong>
            <small class="text-secondary">
                {{ $order->created_at->timezone(config('shop.timezone'))->format('d M Y') }}
                · {{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }}
            </small>
        </div>
        <span class="sgh-status {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
        <strong>{{ $currency }} {{ number_format($order->total) }}</strong>
        <i class="bi bi-chevron-right text-secondary"></i>
    </a>
    @endforeach

    <div class="mt-4 d-flex justify-content-center">{{ $orders->links() }}</div>
    @endif
</div>
@endsection