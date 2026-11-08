@extends('layouts.app')

@php
    // Tells search engines who you are, and that the site has a search box
    $ld = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type' => 'Organization',
                'name'  => config('app.name'),
                'url'   => url('/'),
                'logo'  => asset('assets/images/logo.png'),
            ],
            [
                '@type'           => 'WebSite',
                'name'            => config('app.name'),
                'url'             => url('/'),
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => route('search') . '?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ];
@endphp

@section('title', config('app.name') . ' | Home Décor & Inspiration')

@push('head')
    <link rel="canonical" href="{{ url('/') }}">
    {!! \App\Services\JsonLd::tag($ld) !!}
@endpush

@section('content')

    {{-- 1. Hero --}}
    @include('home.hero')

    {{-- 2. Shop by category --}}
    @include('home.categories')

    {{-- 3. Featured products --}}
    @include('home.products')

    {{-- 4. Free décor helper --}}
    @include('home.helper')

    {{-- 5. Free décor calculators --}}
    @include('home.tools')

    {{-- 6. New arrivals (hidden when there are none) --}}
    @include('home.arrivals')

    {{-- 7. Customer reviews (hidden until there are approved ones) --}}
    @include('home.reviews')

    {{-- 8. Latest from the journal --}}
    @include('home.blog')

    {{-- 9. WhatsApp or email band (hidden until you set a contact in .env) --}}
    @include('home.contact')

@endsection