@extends('layouts.app')

@section('title', ($term !== '' ? 'Search: ' . $term : 'Search') . ' | ' . config('app.name'))

@section('content')

{{-- Banner with a big search box --}}
<section class="sgh-shop-hero">
    <div class="container position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Search</li>
            </ol>
        </nav>

        <h1 class="sgh-shop-hero-title mb-3">Search our shop</h1>

        <form action="{{ route('search') }}" method="GET" role="search" class="sgh-search-box">
            <label for="q" class="visually-hidden">Search products</label>
            <div class="input-group input-group-lg">
                {{-- {{ }} escapes what was typed (safe) --}}
                <input type="search" id="q" name="q" value="{{ $term }}" maxlength="100"
                    class="form-control" placeholder="Wall clock, mirror, Islamic décor…" autofocus>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-search me-1"></i> Search</button>
            </div>
        </form>
    </div>
</section>

<div class="container py-4 py-lg-5">

    @if ($products === null)

    {{-- Nothing searched yet, or the word is too short --}}
    <div class="sgh-empty text-center">
        <i class="bi bi-search fs-1 text-secondary"></i>
        <h2 class="h5 mt-3">{{ $term === '' ? 'What are you looking for?' : 'Type a little more' }}</h2>
        <p class="text-secondary mb-0">
            {{ $term === '' ? 'Search by product name, code or category.' : 'Please type at least 2 characters.' }}
        </p>
    </div>

    @elseif ($products->isEmpty())

    <div class="sgh-empty text-center">
        <i class="bi bi-emoji-neutral fs-1 text-secondary"></i>
        <h2 class="h5 mt-3">No products found</h2>
        <p class="text-secondary">We found nothing for "{{ $term }}". Check the spelling, or browse a category.</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            @foreach ($categories as $cat)
            <a href="{{ url('/shop/' . $cat->slug) }}" class="sgh-pill">{{ $cat->name }}</a>
            @endforeach
        </div>
    </div>

    @else

    <p class="text-secondary small mb-3">
        {{ $products->total() }} {{ \Illuminate\Support\Str::plural('result', $products->total()) }} for "{{ $term }}"
    </p>

    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-2 g-md-3">
        @foreach ($products as $product)
        <div class="col">
            <x-product-card :product="$product" />
        </div>
        @endforeach
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $products->links() }}
    </div>

    @endif
</div>

@endsection