{{-- SHARED FRAME for every calculator: banner, form on the left, result on the right.
     Each calculator fills in @section('fields'). It receives $info, $slug, $tools and $result. --}}
@extends('layouts.app')

@php
    $url    = route('tools.show', $slug);
    $crumbs = [['Home', url('/')], ['Decor tools', route('tools.index')], [$info['name'], $url]];
    $ld     = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => collect($crumbs)->map(fn ($c, $i) => [
            '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1],
        ])->values()->all(),
    ];
@endphp

@section('title', $info['name'] . ' | ' . config('app.name'))
@section('meta_description', $info['description'])

@push('head')
    <link rel="canonical" href="{{ $url }}">
    {!! \App\Services\JsonLd::tag($ld) !!}
@endpush

@section('content')

{{-- ============ BANNER ============ --}}
<section class="sgh-shop-hero">
    <div class="container position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tools.index') }}">Décor tools</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $info['name'] }}</li>
            </ol>
        </nav>
        <p class="sgh-eyebrow mb-2">Décor tools</p>
        <h1 class="sgh-shop-hero-title mb-2">{{ $info['name'] }}</h1>
        <p class="text-secondary mb-0">{{ $info['short'] }}</p>
    </div>
</section>

<div class="container py-4 py-lg-5">
    <div class="row g-4">

        {{-- ============ LEFT: THE FORM ============ --}}
        <div class="col-lg-7">
            {{-- @csrf = security token. The page script sends this form and shows the answer live. --}}
            <form method="POST" action="{{ route('tools.calculate', $slug) }}" data-tool-form novalidate>
                @csrf

                {{-- Shown only when the page was sent without JavaScript and something was wrong --}}
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert" data-server-alert>
                        <i class="bi bi-exclamation-triangle me-1"></i> Please check these boxes:
                        <ul class="mb-0 mt-1">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="sgh-card">
                    <h2 class="sgh-card-title"><span><i class="bi {{ $info['icon'] }}"></i></span> Your measurements</h2>

                    @yield('fields')

                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Calculate</button>
                        <a href="{{ $url }}" class="btn btn-outline-secondary rounded-pill px-4">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        {{-- ============ RIGHT: THE RESULT ============ --}}
        <div class="col-lg-5">
            <div class="sgh-result-wrap">
                <div class="sgh-card" id="result">
                    <h2 class="sgh-card-title"><span><i class="bi bi-calculator"></i></span> Your result</h2>

                    <div id="toolResults" aria-live="polite">
                        @if (! empty($result))
                            @include('tools._result', ['result' => $result])
                        @else
                            <p class="sgh-res-empty">Fill in the measurements to see your result.</p>
                        @endif
                    </div>

                    <p class="sgh-res-disclaimer">
                        These results are estimates to help you plan. Real needs depend on your room, the material
                        and how it is fitted, so please check with your supplier before you buy.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ OTHER TOOLS ============ --}}
    <div class="mt-5">
        <h2 class="h5 mb-3">More décor tools</h2>
        <nav class="sgh-pills" aria-label="Décor tools">
            @foreach ($tools as $key => $tool)
                <a href="{{ route('tools.show', $key) }}" class="sgh-pill {{ $key === $slug ? 'active' : '' }}">{{ $tool['name'] }}</a>
            @endforeach
        </nav>
    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/tools.js') }}" defer></script>
@endpush