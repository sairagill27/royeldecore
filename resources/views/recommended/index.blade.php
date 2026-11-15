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

    // "1 pick", "3 picks"
    $picks = fn (int $n): string => $n . ' ' . \Illuminate\Support\Str::plural('pick', $n);
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

        {{-- Three short notes (not shown while someone is searching) --}}
        @unless ($filtered)
            <div class="row g-3 mb-4">
                @foreach ([
                    ['bi-hand-thumbs-up', 'Hand-picked', 'Chosen by us, one at a time.'],
                    ['bi-bag-heart', 'Useful extras', 'Things that go with your décor, from other stores.'],
                    ['bi-tag', 'Check the price', 'Prices change, so each card sends you to the store for the latest price.'],
                ] as [$chipIcon, $chipTitle, $chipText])
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-3 p-3 bg-white border rounded-4 h-100">
                            <span class="sgh-rec-icon sgh-rec-icon--green"><i class="bi {{ $chipIcon }}" aria-hidden="true"></i></span>
                            <span>
                                <strong class="d-block">{{ $chipTitle }}</strong>
                                <small class="text-secondary">{{ $chipText }}</small>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endunless

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

            {{-- ============ FEATURED PICKS ============ --}}
            @if ($featured->isNotEmpty())
                <section class="sgh-rec-featured mb-5" aria-labelledby="rec-featured">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="sgh-rec-icon"><i class="bi bi-star-fill" aria-hidden="true"></i></span>
                        <h2 class="h4 mb-0" id="rec-featured">Featured picks</h2>
                    </div>
                    <div class="row g-4">
                        @foreach ($featured as $link)
                            <div class="col-12 col-sm-6 col-lg-4">
                                <x-affiliate-card :link="$link" variant="tile" />
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ============ ONE BLOCK PER SECTION ============ --}}
            @foreach ($groups as $title => $items)
                <section class="mb-5" aria-labelledby="rec-section-{{ $loop->index }}">
                    <div class="sgh-rec-head">
                        <h2 class="h4 mb-0" id="rec-section-{{ $loop->index }}">{{ $title }}</h2>
                        <span class="sgh-rec-count">{{ $picks($items->count()) }}</span>
                    </div>
                    <div class="row g-4">
                        @foreach ($items as $link)
                            <div class="col-12 col-sm-6 col-lg-4">
                                <x-affiliate-card :link="$link" variant="tile" />
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            {{-- ============ HOW THESE PICKS WORK, AND THE CLOSING BAND (not while searching) ============ --}}
            @unless ($filtered)
                <section class="mt-5 pt-3" aria-labelledby="rec-how">
                    <h2 class="h4 text-center mb-4" id="rec-how">How these picks work</h2>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="sgh-rec-step">
                                <span class="sgh-rec-step-num">1</span>
                                <h3 class="h6">We choose</h3>
                                <p class="small text-secondary mb-0">We pick items that fit a home décor project, such as tools, lighting and small extras.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="sgh-rec-step">
                                <span class="sgh-rec-step-num">2</span>
                                <h3 class="h6">You visit the store</h3>
                                <p class="small text-secondary mb-0">The button opens the store's own page, where you see the current price and details before you decide.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="sgh-rec-step">
                                <span class="sgh-rec-step-num">3</span>
                                <h3 class="h6">The store may pay us</h3>
                                <p class="small text-secondary mb-0">
                                    If you buy, the store may pay us a small commission, at no extra cost to you.
                                    <a href="{{ route('legal.affiliate') }}">Read our affiliate disclosure</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="sgh-rec-cta">
                    <h2 class="h4 mb-2">Looking for pieces for your walls?</h2>
                    <p class="mb-3 opacity-75">Our own shop has wall art, mirrors, clocks and more.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ url('/shop') }}" class="btn btn-light rounded-pill px-4">Browse the shop</a>
                        <a href="{{ route('helper') }}" class="btn btn-outline-light rounded-pill px-4">Try the Décor Helper</a>
                    </div>
                </section>
            @endunless
        @endif
    @endif
</div>

@endsection