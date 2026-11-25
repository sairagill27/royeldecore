@extends('layouts.app')

@section('title', 'Contact us | ' . config('app.name'))
@section('meta_description', 'Questions about an order, a product or décor ideas? Send us a message and we will reply by email.')

@section('content')

{{-- ============ PAGE HEADER (reuses the shop banner style) ============ --}}
<section class="sgh-tool-head">
    @include('tools._leaves')
    <div class="container position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact</li>
            </ol>
        </nav>
        <div class="sgh-tool-head-row">
            <span class="sgh-tool-head-icon" aria-hidden="true"><i class="bi bi-chat-heart"></i></span>
            <div>
                <span class="sgh-tool-badge"><i class="bi bi-stars" aria-hidden="true"></i> Get in touch</span>
                <h1 class="sgh-tool-head-title">We would love to hear from you</h1>
                <p class="sgh-tool-head-text">Questions about an order, a product or your décor plans? Send us a message.</p>
            </div>
        </div>
    </div>
</section>

<div class="container py-4 py-lg-5">
    <div class="row g-4">

        {{-- ============ LEFT: CONTACT DETAILS ============ --}}
        <div class="col-lg-4">

            <div class="sgh-contact-card mb-3">
                <span class="sgh-contact-icon"><i class="bi bi-envelope"></i></span>
                <div>
                    <strong>Email us</strong>
                    {{-- Uses the sender address from .env (MAIL_FROM_ADDRESS) --}}
                    <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
                </div>
            </div>

            @php
    // Only valid details are shown (set CONTACT_WHATSAPP, CONTACT_PHONE and CONTACT_ADDRESS in .env)
    $cWa = preg_replace('/\D/', '', (string) config('contact.whatsapp'));
    $cWa = (strlen($cWa) >= 10 && strlen($cWa) <= 15) ? $cWa : null;

    $cPhone = preg_replace('/[^0-9+]/', '', (string) config('contact.phone'));
    $cPhone = (strlen(preg_replace('/\D/', '', $cPhone)) >= 7) ? $cPhone : null;

    $cAddress = trim((string) config('contact.address'));
@endphp

            @if ($cWa)
                <div class="sgh-contact-card mb-3">
                    <span class="sgh-contact-icon"><i class="bi bi-whatsapp"></i></span>
                    <div>
                        <strong>WhatsApp</strong>
                        <a href="https://wa.me/{{ $cWa }}?text={{ rawurlencode('Hello ' . config('app.name') . ', I have a question.') }}" target="_blank" rel="noopener noreferrer">Chat with us</a>
                    </div>
                </div>
            @endif

            @if ($cPhone)
                <div class="sgh-contact-card mb-3">
                    <span class="sgh-contact-icon"><i class="bi bi-telephone"></i></span>
                    <div>
                        <strong>Call us</strong>
                        <a href="tel:{{ $cPhone }}">{{ config('contact.phone') }}</a>
                    </div>
                </div>
            @endif

            @if ($cAddress !== '')
                <div class="sgh-contact-card mb-3">
                    <span class="sgh-contact-icon"><i class="bi bi-geo-alt"></i></span>
                    <div>
                        <strong>Visit us</strong>
                        <span class="text-secondary small">{{ $cAddress }}</span>
                    </div>
                </div>
            @endif

            <div class="sgh-contact-card mb-3">
                <span class="sgh-contact-icon"><i class="bi bi-stars"></i></span>
                <div>
                    <strong>Quick question?</strong>
                    <a href="{{ route('assistant') }}">Ask the Décor Assistant</a>
                </div>
            </div>

            <div class="sgh-contact-card">
                <span class="sgh-contact-icon"><i class="bi bi-chat-heart"></i></span>
                <div>
                    <strong>Order questions</strong>
                    <span class="text-secondary small">Please include your order number so we can help faster.</span>
                </div>
            </div>
        </div>

        {{-- ============ RIGHT: THE FORM ============ --}}
        <div class="col-lg-8">
            <div class="sgh-card">
                <h2 class="sgh-card-title"><span><i class="bi bi-send"></i></span> Send a message</h2>

                {{-- @csrf = security token --}}
                <form action="{{ route('contact.store') }}" method="POST" novalidate>
                    @csrf

                    {{-- SPAM TRAP: people never see this field. If it is filled in, a bot did it. --}}
                    <div class="sgh-hp" aria-hidden="true">
                        <label for="website">Leave this field empty</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Your name</label>
                            <input type="text" id="name" name="name"
                                value="{{ old('name', auth()->user()?->name) }}"
                                class="form-control @error('name') is-invalid @enderror"
                                autocomplete="name" maxlength="100" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" id="email" name="email"
                                value="{{ old('email', auth()->user()?->email) }}"
                                class="form-control @error('email') is-invalid @enderror"
                                autocomplete="email" maxlength="255" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" id="subject" name="subject" value="{{ old('subject') }}"
                                class="form-control @error('subject') is-invalid @enderror"
                                maxlength="150" required>
                            @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="message" class="form-label">Message</label>
                            <textarea id="message" name="message" rows="6" maxlength="2000"
                                class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                            @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">At least 10 characters.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 mt-4">
                        <i class="bi bi-send me-2"></i>Send message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection