{{-- SHARED FRAME for every calculator: dark green header, steps on the left, result on the right.
     Each calculator fills in @section('fields') and optionally @section('how').
     It receives $info, $slug, $tools and $result. --}}
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

{{-- ============ HEADER (dark green, in the shop's own colours) ============ --}}
<section class="sgh-tool-head">
    @include('tools._leaves')
    <div class="container position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tools.index') }}">Décor tools</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $info['name'] }}</li>
            </ol>
        </nav>

        <div class="sgh-tool-head-row">
            <span class="sgh-tool-head-icon" aria-hidden="true"><i class="bi {{ $info['icon'] }}"></i></span>
            <div>
                <span class="sgh-tool-badge"><i class="bi bi-stars" aria-hidden="true"></i> Free tool</span>
                <h1 class="sgh-tool-head-title">{{ $info['name'] }}</h1>
                <p class="sgh-tool-head-text">{{ $info['short'] }}</p>
            </div>
        </div>

        @if (! empty($info['gets']))
            <ul class="sgh-tool-gets">
                @foreach ($info['gets'] as $item)
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> {{ $item }}</li>
                @endforeach
            </ul>
        @endif
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

                @yield('fields')

                <div class="sgh-tool-actions">
                    <button type="submit" class="btn sgh-btn-dark rounded-pill px-5 py-2">
                        <i class="bi bi-calculator me-1" aria-hidden="true"></i> Calculate
                    </button>
                    <a href="{{ $url }}" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                        <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- ============ RIGHT: THE RESULT (dark green card) ============ --}}
        <div class="col-lg-5">
            <div class="sgh-result-wrap">
                <div class="sgh-result-card" id="result">
                    <h2 class="sgh-result-title"><i class="bi bi-calculator" aria-hidden="true"></i> Your result</h2>

                    <div id="toolResults" aria-live="polite">
                        @if (! empty($result))
                            @include('tools._result', ['result' => $result])
                        @else
                            <div class="sgh-res-empty">
                                <i class="bi {{ $info['icon'] }}" aria-hidden="true"></i>
                                <p>Fill in the measurements to see your result.</p>
                            </div>
                        @endif
                    </div>

                    <p class="sgh-res-disclaimer">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        These results are estimates to help you plan. Real needs depend on your room, the material
                        and how it is fitted, so please check with your supplier before you buy.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ HOW WE CALCULATE ============ --}}
    @hasSection('how')
        <section class="sgh-how mt-5" aria-labelledby="how-title">
            <h2 id="how-title" class="h4 mb-3">How we calculate</h2>
            @yield('how')
        </section>
    @endif

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