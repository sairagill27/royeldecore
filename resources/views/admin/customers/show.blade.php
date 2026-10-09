@extends('layouts.admin')

@section('title', $customer->name)
@section('heading', 'Customer')

@section('content')

@php $currency = config('shop.currency'); @endphp

<p class="mb-3">
    <a href="{{ route('admin.customers.index') }}"><i class="bi bi-arrow-left"></i> Back to customers</a>
</p>

<div class="row g-3">

    {{-- ============ LEFT: PROFILE ============ --}}
    <div class="col-lg-4">

        <div class="sgh-panel mb-3">
            {{-- {{ }} escapes the text, so nothing typed by a customer can run as code --}}
            <h2 class="h5 mb-1">{{ $customer->name }}</h2>
            <p class="small mb-1"><i class="bi bi-envelope text-secondary"></i> <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a></p>
            <p class="small text-secondary mb-3">Joined {{ $customer->created_at->timezone(config('shop.timezone'))->format('d M Y') }}</p>

            <span class="badge {{ $customer->is_active ? 'sgh-badge-ok' : 'sgh-badge-out' }}">
                {{ $customer->is_active ? 'Active' : 'Blocked' }}
            </span>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="sgh-stat sgh-stat--gold">
                    <div><small>Orders</small><strong>{{ $orders->count() }}</strong></div>
                </div>
            </div>
            <div class="col-6">
                <div class="sgh-stat sgh-stat--sage">
                    <div><small>Spent</small><strong>{{ $currency }} {{ number_format($spent) }}</strong></div>
                </div>
            </div>
        </div>

        {{-- BLOCK / UNBLOCK: admins only --}}
        @if (auth()->user()->isAdmin())
        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div>
                    <h2>Account access</h2>
                </div>
            </div>

            <form action="{{ route('admin.customers.toggle', $customer) }}" method="POST"
                @if ($customer->is_active)
                onsubmit="return confirm('Block this customer? They will be logged out and cannot log in.')"
                @endif>
                @csrf
                @method('PATCH')

                @if ($customer->is_active)
                <p class="small text-secondary">Blocking logs the customer out and stops them from logging in. Their orders stay.</p>
                <button type="submit" class="btn btn-outline-danger w-100 rounded-pill">Block account</button>
                @else
                <p class="small text-secondary">This account is blocked.</p>
                <button type="submit" class="btn btn-primary w-100 rounded-pill">Unblock account</button>
                @endif
            </form>
        </div>
        @endif
    </div>

    {{-- ============ RIGHT: ORDERS ============ --}}
    <div class="col-lg-8">
        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div>
                    <h2>Orders</h2>
                </div>
            </div>

            @if ($orders->isEmpty())
            <p class="sgh-panel-empty"><i class="bi bi-receipt"></i> This customer has not placed an order yet.</p>
            @else
            <div class="table-responsive">
                <table class="table sgh-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th class="text-center">Items</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $order->order_number) }}" class="fw-semibold text-decoration-none">{{ $order->order_number }}</a>
                                <small class="d-block text-secondary">{{ $order->created_at->timezone(config('shop.timezone'))->format('d M Y, h:i A') }}</small>
                            </td>
                            <td class="text-center">{{ $order->items_count }}</td>
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
</div>

@endsection