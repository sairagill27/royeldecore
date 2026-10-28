{{-- Shown for a calculator that is not built yet. Search engines are asked to skip it. --}}
@extends('layouts.app')

@section('title', $info['name'] . ' | ' . config('app.name'))

@push('head')
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')

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
                <span class="sgh-tool-badge"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Coming soon</span>
                <h1 class="sgh-tool-head-title">{{ $info['name'] }}</h1>
                <p class="sgh-tool-head-text">{{ $info['short'] }}</p>
            </div>
        </div>
    </div>
</section>

<div class="container py-4 py-lg-5">
    <div class="sgh-empty text-center">
        <i class="bi {{ $info['icon'] }} fs-1 text-secondary"></i>
        <h2 class="h5 mt-3">This calculator is coming soon</h2>
        <p class="text-secondary">We are still building it. In the meantime, try one of these:</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            @foreach ($tools as $key => $tool)
                @if ($tool['ready'])
                    <a href="{{ route('tools.show', $key) }}" class="btn btn-primary rounded-pill px-4">{{ $tool['name'] }}</a>
                @endif
            @endforeach
        </div>
    </div>
</div>

@endsection