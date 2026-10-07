@extends('layouts.app')

@section('title', 'My Account | ' . config('app.name'))

@section('content')
<div class="container py-4 py-lg-5">

    {{-- auth()->user() is the logged-in person. {{ }} escapes the name (safe). --}}
    <h1 class="sgh-section-title mb-1">Hello, {{ auth()->user()->name }}</h1>
    <p class="text-secondary mb-4">{{ auth()->user()->email }}</p>

    <div class="row row-cols-1 row-cols-md-3 g-3">
        <div class="col">
            <div class="sgh-filter-box h-100">
                <h2 class="h6">My orders</h2>
                <p class="small text-secondary mb-2">See your orders and their status.</p>
                <a href="{{ route('account.orders') }}" class="small fw-semibold">View my orders <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col">
            <div class="sgh-filter-box h-100">
                <h2 class="h6">Wishlist</h2>
                <p class="small text-secondary mb-0">Products you saved for later.</p>
            </div>
        </div>
        <div class="col">
            <div class="sgh-filter-box h-100">
                <h2 class="h6">Profile and password</h2>
                <p class="small text-secondary mb-0">Update your details.</p>
            </div>
        </div>
    </div>

    <form action="{{ url('/logout') }}" method="POST" class="mt-4">
        @csrf
        <button type="submit" class="btn btn-outline-primary rounded-pill px-4">Log out</button>
    </form>
</div>
@endsection