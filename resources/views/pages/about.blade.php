@extends('layouts.app')

@section('title', 'About us | ' . config('app.name'))
@section('meta_description', 'Home décor for every room: wall décor, mirrors, Islamic décor, wall clocks and decorative pieces.')

@section('content')

{{-- Banner --}}
<section class="sgh-tool-head">
    @include('tools._leaves')
    <div class="container position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">About</li>
            </ol>
        </nav>
        <div class="sgh-tool-head-row">
            <span class="sgh-tool-head-icon" aria-hidden="true"><i class="bi bi-house-heart"></i></span>
            <div>
                <span class="sgh-tool-badge"><i class="bi bi-stars" aria-hidden="true"></i> About us</span>
                <h1 class="sgh-tool-head-title">Making every room feel like home</h1>
                <p class="sgh-tool-head-text">Décor you will love, and ideas to help you place it.</p>
            </div>
        </div>
    </div>
</section>

<div class="container py-4 py-lg-5">

    {{-- Our story --}}
    <div class="row justify-content-center mb-5">
        <div class="col-lg-8">
            <h2 class="h3 mb-3">Our story</h2>
            <p class="text-secondary">
                {{ config('app.name') }} is a home décor shop for people who want their space to feel warm,
                personal and well put together. We offer wall décor, mirrors, Islamic décor, wall clocks,
                decorative pieces, organizers and personalized décor.
            </p>
            <p class="text-secondary mb-0">
                <mark class="sgh-todo">[Add your own story here: who you are, why you started, what you care about. Two or three short paragraphs is plenty.]</mark>
            </p>
        </div>
    </div>

    {{-- Three promises --}}
    <div class="row row-cols-1 row-cols-md-3 g-3 mb-5">
        <div class="col">
            <div class="sgh-contact-card h-100">
                <span class="sgh-contact-icon"><i class="bi bi-house-heart"></i></span>
                <div>
                    <strong>Décor for every room</strong>
                    <span class="text-secondary small">Pieces for your walls, shelves and corners, in styles that suit real homes.</span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="sgh-contact-card h-100">
                <span class="sgh-contact-icon"><i class="bi bi-cash-coin"></i></span>
                <div>
                    <strong>Clear and simple</strong>
                    <span class="text-secondary small">Prices and stock are shown as they are, and you pay when your order arrives.</span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="sgh-contact-card h-100">
                <span class="sgh-contact-icon"><i class="bi bi-chat-heart"></i></span>
                <div>
                    <strong>Real help</strong>
                    <span class="text-secondary small">Questions about an order or a product? Send us a message and we reply by email.</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Explore --}}
    <div class="mb-5">
        <h2 class="h3 text-center mb-4">Explore {{ config('app.name') }}</h2>
        <div class="row row-cols-2 row-cols-lg-4 g-3">
            @foreach ([
                ['Shop décor', url('/shop'), 'bi-bag-heart', 'Wall art, mirrors, clocks and more'],
                ['Read the journal', url('/blog'), 'bi-journal-richtext', 'Ideas and guides for your home'],
                ['Décor tools', route('tools.index'), 'bi-rulers', 'Free calculators to plan your room'],
                ['Décor Helper', route('helper'), 'bi-magic', 'Ideas by room, style and budget'],
            ] as [$exTitle, $exUrl, $exIcon, $exText])
                <div class="col">
                    <a href="{{ $exUrl }}" class="card h-100 border-0 shadow-sm rounded-4 text-decoration-none sgh-lift">
                        <div class="card-body text-center p-4">
                            <span class="sgh-rec-icon sgh-rec-icon--green mb-3"><i class="bi {{ $exIcon }}" aria-hidden="true"></i></span>
                            <h3 class="h6 mb-1 text-body">{{ $exTitle }}</h3>
                            <p class="small text-secondary mb-0">{{ $exText }}</p>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Call to action --}}
    <div class="sgh-cta-band text-center">
        <h2 class="h4 mb-2">Ready to refresh your space?</h2>
        <p class="text-secondary mb-3">Browse the shop or get in touch.</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ url('/shop') }}" class="btn btn-light rounded-pill px-4">Shop décor</a>
            <a href="{{ route('contact') }}" class="btn btn-outline-light rounded-pill px-4">Contact us</a>
        </div>
    </div>
</div>

@endsection