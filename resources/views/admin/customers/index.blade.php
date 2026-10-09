@extends('layouts.admin')

@section('title', 'Customers')
@section('heading', 'Customers')

@section('content')

@php $currency = config('shop.currency'); @endphp

<div class="sgh-panel">

    <form action="{{ route('admin.customers.index') }}" method="GET" class="sgh-admin-filters mb-3" role="search">
        <label for="q" class="visually-hidden">Search customers</label>
        <input type="search" id="q" name="q" value="{{ request('q') }}"
            class="form-control form-control-sm" placeholder="Name or email…">
        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Search</button>
        @if (request('q'))
        <a href="{{ route('admin.customers.index') }}" class="btn btn-link btn-sm">Reset</a>
        @endif
    </form>

    <p class="small text-secondary mb-3">{{ $customers->total() }} {{ \Illuminate\Support\Str::plural('customer', $customers->total()) }}</p>

    @if ($customers->isEmpty())

    <p class="sgh-panel-empty"><i class="bi bi-people"></i> No customers found.</p>

    @else
    <div class="table-responsive">
        <table class="table sgh-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Joined</th>
                    <th class="text-center">Orders</th>
                    <th class="text-end">Spent</th>
                    <th>Account</th>
                    <th class="text-end"><span class="visually-hidden">Open</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($customers as $customer)
                <tr>
                    <td>
                        <a href="{{ route('admin.customers.show', $customer) }}" class="fw-semibold text-decoration-none">{{ $customer->name }}</a>
                        <small class="d-block text-secondary">{{ $customer->email }}</small>
                    </td>
                    <td>{{ $customer->created_at->timezone(config('shop.timezone'))->format('d M Y') }}</td>
                    <td class="text-center">{{ $customer->orders_count }}</td>
                    <td class="text-end fw-semibold">{{ $currency }} {{ number_format($customer->spent ?? 0) }}</td>
                    <td>
                        <span class="badge {{ $customer->is_active ? 'sgh-badge-ok' : 'sgh-badge-out' }}">
                            {{ $customer->is_active ? 'Active' : 'Blocked' }}
                        </span>
                    </td>
                    <td>
                        <div class="sgh-row-actions">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="sgh-icon-action"
                                title="Open" aria-label="Open {{ $customer->name }}"><i class="bi bi-eye"></i></a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $customers->links() }}
    </div>
    @endif
</div>

@endsection