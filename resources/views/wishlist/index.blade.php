@extends('layouts.app')

@section('title', 'My Wishlist | ' . config('app.name'))

{{-- A private page: search engines are asked to skip it --}}
@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')

<section class="sgh-shop-hero">
    <div class="container position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('account') }}">My account</a></li>
                <li class="breadcrumb-item active" aria-current="page">Wishlist</li>
            </ol>
        </nav>
        <p class="sgh-eyebrow mb-2">Saved for later</p>
        <h1 class="sgh-shop-hero-title mb-2">My wishlist</h1>
        <p class="text-secondary mb-0">The pieces you love, all in one place.</p>
    </div>
</section>

<div class="container py-4 py-lg-5">

    @if ($unavailable > 0)
        <div class="alert alert-secondary" role="status">
            <i class="bi bi-info-circle me-1"></i>
            {{ $unavailable }} {{ \Illuminate\Support\Str::plural('product', $unavailable) }} on your list
            {{ $unavailable === 1 ? 'is' : 'are' }} no longer available, so {{ $unavailable === 1 ? 'it is' : 'they are' }} not shown here.
        </div>
    @endif

    @if ($products->isEmpty())
        <div class="sgh-empty text-center">
            <i class="bi bi-heart fs-1 text-secondary"></i>
            <h2 class="h5 mt-3">Your wishlist is empty</h2>
            <p class="text-secondary">Tap the heart on any product to save it here.</p>
            <a href="{{ route('shop') }}" class="btn btn-primary rounded-pill px-4">Browse the shop</a>
        </div>
    @else
        <p class="text-secondary small mb-3">{{ $products->count() }} {{ \Illuminate\Support\Str::plural('item', $products->count()) }} saved</p>

        <div class="row row-cols-2 row-cols-lg-4 g-2 g-md-3">
            @foreach ($products as $product)
                <div class="col"><x-product-card :product="$product" /></div>
            @endforeach
        </div>
    @endif
</div>

@endsection