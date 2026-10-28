@extends('layouts.app')

@php
    $ld = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Decor tools', 'item' => route('tools.index')],
        ],
    ];
@endphp

@section('title', 'Décor Tools | ' . config('app.name'))
@section('meta_description', 'Free décor calculators: paint, flooring, wallpaper, curtains and a room budget planner.')

@push('head')
    <link rel="canonical" href="{{ route('tools.index') }}">
    {!! \App\Services\JsonLd::tag($ld) !!}
@endpush

@section('content')

<section class="sgh-shop-hero">
    <div class="container position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Décor tools</li>
            </ol>
        </nav>
        <p class="sgh-eyebrow mb-2">Free to use</p>
        <h1 class="sgh-shop-hero-title mb-2">Décor tools</h1>
        <p class="text-secondary mb-0">Plan your room with simple calculators. Results are estimates to help you shop with confidence.</p>
    </div>
</section>

<div class="container py-4 py-lg-5">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 g-lg-4">
        @foreach ($tools as $key => $tool)
            <div class="col">
                @if ($tool['ready'])
                    <a href="{{ route('tools.show', $key) }}" class="sgh-tool-card">
                        <span class="sgh-tool-icon"><i class="bi {{ $tool['icon'] }}" aria-hidden="true"></i></span>
                        <h2>{{ $tool['name'] }}</h2>
                        <p>{{ $tool['short'] }}</p>
                        <span class="sgh-tool-go">Open the calculator <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                    </a>
                @else
                    <div class="sgh-tool-card is-soon">
                        <span class="sgh-tool-icon"><i class="bi {{ $tool['icon'] }}" aria-hidden="true"></i></span>
                        <h2>{{ $tool['name'] }}</h2>
                        <p>{{ $tool['short'] }}</p>
                        <span class="badge sgh-badge-low align-self-start">Coming soon</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

@endsection