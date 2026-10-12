{{-- Shared frame for all legal pages: banner, side menu, white text box.
     Each legal page fills in: title, heading, updated, body. --}}
@extends('layouts.app')

@section('content')

@php
// The side menu: web address => name
$legalLinks = [
'privacy-policy' => 'Privacy Policy',
'terms' => 'Terms & Conditions',
'disclaimer' => 'Disclaimer',
'affiliate-disclosure' => 'Affiliate Disclosure',
'cookies' => 'Cookie Information',
];
@endphp

{{-- Banner (same style as the shop banner) --}}
<section class="sgh-shop-hero">
    <div class="container position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">@yield('heading')</li>
            </ol>
        </nav>
        <p class="sgh-eyebrow mb-2">Legal</p>
        <h1 class="sgh-shop-hero-title mb-2">@yield('heading')</h1>
        <p class="text-secondary mb-0">Last updated: @yield('updated')</p>
    </div>
</section>

<div class="container py-4 py-lg-5">
    <div class="row g-4">

        {{-- Side menu: stays in view on large screens --}}
        <aside class="col-lg-3">
            <nav class="sgh-filter-box sgh-legal-nav" aria-label="Legal pages">
                <h2 class="sgh-filter-title">Legal</h2>
                @foreach ($legalLinks as $path => $label)
                <a href="{{ url('/' . $path) }}"
                    class="sgh-filter-link {{ request()->is($path) ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </aside>

        {{-- The page text --}}
        <article class="col-lg-9">
            <div class="sgh-legal">
                @yield('body')
            </div>
        </article>
    </div>
</div>

@endsection