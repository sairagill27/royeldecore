@extends('layouts.app')

@php
    $currency = config('shop.currency', 'Rs.');

    // "Under Rs. 5,000", "Rs. 5,000 to 15,000"... built from the shop currency
    $budgetLabel = function (string $key, array $b) use ($currency): string {
        if (isset($b['min'], $b['max'])) {
            return $currency . ' ' . number_format($b['min']) . ' to ' . number_format($b['max']);
        }
        if (isset($b['max'])) {
            return 'Under ' . $currency . ' ' . number_format($b['max']);
        }
        if (isset($b['min'])) {
            return 'Over ' . $currency . ' ' . number_format($b['min']);
        }

        return 'Any budget';
    };

    // WhatsApp: only shown when a valid number is set in .env (CONTACT_WHATSAPP)
    $wa = preg_replace('/\D/', '', (string) config('contact.whatsapp'));
    $wa = (strlen($wa) >= 10 && strlen($wa) <= 15) ? $wa : null;

    $roomName  = $room ? $rooms[$room]['label'] : '';
    $styleName = $style ? $styles[$style]['label'] : '';
    $toolKey   = $room ? ($rooms[$room]['tool'] ?? null) : null;
    $tool      = $toolKey ? config('tools.' . $toolKey) : null;
@endphp

@section('title', 'Décor Helper | ' . config('app.name'))
@section('meta_description', 'Free décor helper: pick your room, style and budget and get a mood board and product ideas from our shop.')

@push('head')
    <link rel="canonical" href="{{ route('helper') }}">
    {{-- Results depend on what was chosen, so search engines skip them --}}
    @if ($result || request()->query->count() > 0)
        <meta name="robots" content="noindex, follow">
    @endif
@endpush

@section('content')

{{-- ============ HEADER (deep green, same as the shop and the tools) ============ --}}
<section class="sgh-tool-head">
    @include('tools._leaves')
    <div class="container position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Décor Helper</li>
            </ol>
        </nav>
        <div class="sgh-tool-head-row">
            <span class="sgh-tool-head-icon" aria-hidden="true"><i class="bi bi-magic"></i></span>
            <div>
                <span class="sgh-tool-badge"><i class="bi bi-stars" aria-hidden="true"></i> Free, no sign-up</span>
                <h1 class="sgh-tool-head-title">Décor Helper</h1>
                <p class="sgh-tool-head-text">Pick your room, your style and your budget. We build a mood board and suggest pieces from our shop.</p>
            </div>
        </div>
    </div>
</section>

<div class="container py-4 py-lg-5">

    {{-- ============ THE FORM (a normal GET form, so a result can be shared as a link) ============ --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4 p-lg-5">
            <form method="GET" action="{{ route('helper') }}">
                <input type="hidden" name="go" value="1">

                @if ($error)
                    <div class="alert alert-warning" role="alert"><i class="bi bi-exclamation-triangle me-1"></i> {{ $error }}</div>
                @endif

                <fieldset class="mb-4">
                    <legend class="h6 mb-2">1. Which room?</legend>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($rooms as $key => $item)
                            <input type="radio" class="btn-check" name="room" id="room-{{ $key }}" value="{{ $key }}" autocomplete="off" required @checked($room === $key)>
                            <label class="btn btn-outline-primary rounded-pill" for="room-{{ $key }}"><i class="bi {{ $item['icon'] }} me-1" aria-hidden="true"></i>{{ $item['label'] }}</label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="mb-4">
                    <legend class="h6 mb-2">2. Which style do you like?</legend>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($styles as $key => $item)
                            <input type="radio" class="btn-check" name="style" id="style-{{ $key }}" value="{{ $key }}" autocomplete="off" required @checked($style === $key)>
                            <label class="btn btn-outline-primary rounded-pill" for="style-{{ $key }}"><i class="bi {{ $item['icon'] }} me-1" aria-hidden="true"></i>{{ $item['label'] }}</label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="mb-4">
                    <legend class="h6 mb-2">3. Your budget for one piece</legend>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($budgets as $key => $item)
                            <input type="radio" class="btn-check" name="budget" id="budget-{{ $key }}" value="{{ $key }}" autocomplete="off" @checked($budget === $key)>
                            <label class="btn btn-outline-primary rounded-pill" for="budget-{{ $key }}">{{ $budgetLabel($key, $item) }}</label>
                        @endforeach
                    </div>
                </fieldset>

                <button type="submit" class="btn sgh-btn-dark btn-lg rounded-pill px-5">
                    <i class="bi bi-magic me-2" aria-hidden="true"></i>Show my ideas
                </button>
            </form>
        </div>
    </div>

    {{-- ============ RESULTS ============ --}}
    @if ($result)

        @if ($result['products']->isEmpty())
            <div class="sgh-empty text-center">
                <i class="bi bi-search fs-1 text-secondary"></i>
                <h2 class="h5 mt-3">We have no pieces in this budget right now</h2>
                <p class="text-secondary">Try another budget, or browse the whole shop.</p>
                <a href="{{ url('/shop') }}" class="btn btn-primary rounded-pill px-4">Browse the shop</a>
            </div>
        @else

            @if (! $result['matched'])
                <div class="alert alert-secondary" role="status">
                    <i class="bi bi-info-circle me-1"></i>
                    We could not find exact matches for this style, so here are some of our newest and most popular pieces in your budget.
                </div>
            @endif

            <div class="row g-4 mb-5">

                {{-- Mood board: the real photos of matching products --}}
                <div class="col-lg-5">
                    <h2 class="h4 mb-1">Your mood board</h2>
                    <p class="text-secondary small">{{ $roomName }} &middot; {{ $styleName }}. Real pieces from our shop.</p>

                    @if ($result['board']->isNotEmpty())
                        <div class="row row-cols-2 g-2">
                            @foreach ($result['board'] as $piece)
                                <div class="col">
                                    <a href="{{ route('products.show', $piece->slug) }}"
                                       class="d-block position-relative ratio ratio-1x1 rounded-4 overflow-hidden shadow-sm sgh-board-tile">
                                        <img src="{{ asset('storage/' . $piece->image) }}" alt="{{ $piece->name }}"
                                             class="object-fit-cover" width="400" height="400" loading="lazy">
                                        <span class="position-absolute bottom-0 start-0 end-0 p-2 small text-white sgh-board-caption">
                                            {{ \Illuminate\Support\Str::limit($piece->name, 26) }}
                                        </span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-secondary small">The matching pieces have no photos yet. See them below.</p>
                    @endif
                </div>

                {{-- Tips and next steps --}}
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h2 class="h4 mb-3">Ideas for your {{ strtolower($roomName) }}</h2>
                            <ul class="mb-3 ps-3">
                                @foreach ($rooms[$room]['tips'] as $tip)
                                    <li class="mb-1">{{ $tip }}</li>
                                @endforeach
                                <li class="mb-1">{{ $styles[$style]['tip'] }}</li>
                            </ul>

                            <div class="d-flex flex-wrap gap-2">
                                @if ($tool && ! empty($tool['ready']))
                                    <a href="{{ route('tools.show', $toolKey) }}" class="btn btn-outline-primary rounded-pill">
                                        <i class="bi {{ $tool['icon'] }} me-1" aria-hidden="true"></i>{{ $tool['name'] }}
                                    </a>
                                @endif
                                @if ($wa)
                                    <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Hello, I need help choosing décor for my ' . strtolower($roomName) . ' (' . strtolower($styleName) . ' style).') }}"
                                       target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary rounded-pill">
                                        <i class="bi bi-whatsapp me-1" aria-hidden="true"></i>Ask us on WhatsApp
                                    </a>
                                @endif
                                <a href="{{ route('helper') }}" class="btn btn-link">Start again</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="h4 mb-3">Top picks for you</h2>
            <div class="row row-cols-2 row-cols-lg-4 g-2 g-md-3">
                @foreach ($result['products'] as $piece)
                    <div class="col"><x-product-card :product="$piece" /></div>
                @endforeach
            </div>
        @endif
    @endif

    <p class="small text-secondary mt-5 mb-0">
        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
        This helper is a guided finder, not an AI. It matches your choices to the names and descriptions of our products,
        so it is free and instant. The pictures are real product photos.
    </p>
</div>

@endsection