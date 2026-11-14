@extends('layouts.app')

@php
    $ld = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Recommended', 'item' => route('recommended')],
        ],
    ];
@endphp

@section('title', 'Recommended for your home | ' . config('app.name'))
@section('meta_description', 'Home décor picks from other stores, for the things we do not sell ourselves. Some links are affiliate links.')

@push('head')
    <link rel="canonical" href="{{ route('recommended') }}">
    {{-- Searches, filters and an empty page are kept out of search engines --}}
    @if ($filtered || ! $hasAny)
        <meta name="robots" content="noindex, follow">
    @endif
    {!! \App\Services\JsonLd::tag($ld) !!}
@endpush

@section('content')

{{-- ============ HEADER (deep green, same as the shop and the tools) ============ --}}
<section class="sgh-tool-head">
    @include('tools._leaves')
    <div class="container position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Recommended</li>
            </ol>
        </nav>
        <div class="sgh-tool-head-row">
            <span class="sgh-tool-head-icon" aria-hidden="true"><i class="bi bi-bag-heart"></i></span>
            <div>
                <span class="sgh-tool-badge"><i class="bi bi-stars" aria-hidden="true"></i> Hand-picked</span>
                <h1 class="sgh-tool-head-title">Recommended for your home</h1>
                <p class="sgh-tool-head-text">Useful finds from other stores, for the things we do not sell ourselves.</p>
            </div>
        </div>
    </div>
</section>

<div class="container py-4 py-lg-5">

    @if (! $hasAny)
        <div class="sgh-empty text-center">
            <i class="bi bi-bag-heart fs-1 text-secondary"></i>
            <h2 class="h5 mt-3">No recommendations yet</h2>
            <p class="text-secondary">Please check back soon, or browse our own pieces in the shop.</p>
            <a href="{{ url('/shop') }}" class="btn btn-primary rounded-pill px-4">Browse the shop</a>
        </div>
    @else
        <x-affiliate-disclosure />

        {{-- Search and section buttons --}}
        <form action="{{ route('recommended') }}" method="GET" role="search" class="mb-3">
            @if ($section !== '') <input type="hidden" name="section" value="{{ $section }}"> @endif
            <label for="rec-q" class="visually-hidden">Search recommendations</label>
            <div class="input-group" style="max-width: 32rem;">
                <input type="search" id="rec-q" name="q" value="{{ $q }}" maxlength="80" class="form-control" placeholder="Search recommendations&hellip;" autocomplete="off">
                <button type="submit" class="btn sgh-btn-dark px-4"><i class="bi bi-search me-1" aria-hidden="true"></i>Search</button>
            </div>
        </form>

        @if ($sectionNames->isNotEmpty())
            <nav class="sgh-pills mb-4" aria-label="Sections">
                <a href="{{ route('recommended', array_filter(['q' => $q])) }}" class="sgh-pill {{ $section === '' ? 'active' : '' }}">All</a>
                @foreach ($sectionNames as $name)
                    <a href="{{ route('recommended', array_filter(['section' => $name, 'q' => $q])) }}"
                       class="sgh-pill {{ $section === $name ? 'active' : '' }}">{{ $name }}</a>
                @endforeach
            </nav>
        @endif

        @if ($filtered)
            <p class="text-secondary small">
                {{ $links->count() }} {{ \Illuminate\Support\Str::plural('pick', $links->count()) }} found.
                <a href="{{ route('recommended') }}">Clear search</a>
            </p>
        @endif

        @if ($links->isEmpty())
            <div class="sgh-empty text-center">
                <i class="bi bi-search fs-1 text-secondary"></i>
                <h2 class="h5 mt-3">No picks match your search</h2>
                <p class="text-secondary">Try another word, or clear the search.</p>
                <a href="{{ route('recommended') }}" class="btn btn-primary rounded-pill px-4">Show all picks</a>
            </div>
        @else

            @if ($featured->isNotEmpty())
                <h2 class="h4 mb-3">Featured picks</h2>
                <div class="row g-4 mb-4">
                    @foreach ($featured as $link)
                        <div class="col-12 col-sm-6 col-lg-4">
                            <x-affiliate-card :link="$link" variant="tile" />
                        </div>
                    @endforeach
                </div>
            @endif

            @foreach ($groups as $title => $items)
                <h2 class="h4 mb-3 mt-4">{{ $title }}</h2>
                <div class="row g-4">
                    @foreach ($items as $link)
                        <div class="col-12 col-sm-6 col-lg-4">
                            <x-affiliate-card :link="$link" variant="tile" />
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif
    @endif
</div>

@endsection