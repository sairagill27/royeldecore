@extends('layouts.app')

@php
    $app = config('app.name');

    // "Talk to a person": WhatsApp only when a valid number is set in .env (CONTACT_WHATSAPP)
    $wa = preg_replace('/\D/', '', (string) config('contact.whatsapp'));
    $wa = (strlen($wa) >= 10 && strlen($wa) <= 15) ? $wa : null;
@endphp

@section('title', 'Décor Assistant | ' . $app)
@section('meta_description', 'Ask the Décor Assistant about your room, your style or your budget and get ideas from our shop.')

@push('head')
    <link rel="canonical" href="{{ route('assistant') }}">
@endpush

@section('content')

{{-- ============ HEADER (deep green, same as the shop and the tools) ============ --}}
<section class="sgh-tool-head">
    @include('tools._leaves')
    <div class="container position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Décor Assistant</li>
            </ol>
        </nav>
        <div class="sgh-tool-head-row">
            <span class="sgh-tool-head-icon" aria-hidden="true"><i class="bi bi-chat-heart"></i></span>
            <div>
                <span class="sgh-tool-badge"><i class="bi bi-stars" aria-hidden="true"></i> Free, no sign-up</span>
                <h1 class="sgh-tool-head-title">Décor Assistant</h1>
                <p class="sgh-tool-head-text">Ask about your room, your style or your budget. I suggest real pieces from our shop.</p>
            </div>
        </div>
    </div>
</section>

<div class="container py-4 py-lg-5">
    <div class="row g-4">

        {{-- ============ THE CHAT ============ --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow rounded-4 overflow-hidden" id="chat"
                 data-ask-url="{{ route('assistant.ask') }}" data-csrf="{{ csrf_token() }}" data-max="{{ $max }}">

                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between gap-2 p-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="sgh-avatar" aria-hidden="true"><i class="bi bi-stars"></i></span>
                        <div class="lh-sm">
                            <strong class="d-block">Décor Assistant</strong>
                            @if ($ai)
                                <small class="text-success"><i class="bi bi-circle-fill me-1" style="font-size:.5rem" aria-hidden="true"></i>Online &middot; AI assistant</small>
                            @else
                                <small class="text-secondary"><i class="bi bi-circle-fill me-1" style="font-size:.5rem" aria-hidden="true"></i>Simple mode &middot; matches your words to our products</small>
                            @endif
                        </div>
                    </div>
                    <button type="button" id="chatClear" class="btn btn-outline-secondary btn-sm rounded-pill">
                        <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>New chat
                    </button>
                </div>

                <div class="sgh-chat-log" id="chatLog" role="log" aria-live="polite" aria-label="Conversation">
                    <div class="sgh-msg sgh-msg--bot sgh-keep">
                        <span class="sgh-avatar" aria-hidden="true"><i class="bi bi-stars"></i></span>
                        <div class="sgh-bubble sgh-bubble--bot">
                            <p class="mb-0">Hello! I am the {{ $app }} décor assistant. Tell me about your room, your style or your budget, and I will suggest pieces from our shop.</p>
                        </div>
                    </div>

                    {{-- Starter questions: one click sends them --}}
                    <div class="d-flex flex-wrap gap-2 ms-5 mb-2 sgh-keep" id="chatStarters">
                        @foreach ($categories as $name)
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" data-ask="Show me {{ $name }}">Show me {{ $name }}</button>
                        @endforeach
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" data-ask="Ideas for a small living room">Ideas for a small living room</button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" data-ask="Gift ideas for a new home">Gift ideas for a new home</button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" data-ask="How does cash on delivery work?">How does cash on delivery work?</button>
                    </div>
                </div>

                <div class="card-footer bg-white border-0 p-3">
                    <form id="chatForm" novalidate>
                        <div class="input-group">
                            <label for="chatInput" class="visually-hidden">Your question</label>
                            <textarea id="chatInput" class="form-control sgh-chat-input" rows="1" maxlength="{{ $max }}"
                                      placeholder="Type your question..." autocomplete="off"></textarea>
                            <button type="submit" id="chatSend" class="btn sgh-btn-dark px-4">
                                <i class="bi bi-send" aria-hidden="true"></i><span class="visually-hidden">Send</span>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between small text-secondary mt-2">
                            <span>Enter to send &middot; Shift+Enter for a new line</span>
                            <span id="chatCount">0 / {{ $max }}</span>
                        </div>
                    </form>
                </div>
            </div>

            <p class="small text-secondary mt-3 mb-0">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                @if ($ai)
                    AI answers can be wrong, so please check the price and details on the product page.
                    Please do not share personal details such as phone numbers, addresses or passwords.
                    Your questions are sent to Google's Gemini service to write the answer, and on Google's free plan Google may use them to improve its products.
                    We do not save your chat on our servers.
                @else
                    This assistant is in simple mode: it matches your words to the names and descriptions of our products.
                    We do not save your chat on our servers.
                @endif
            </p>
        </div>

        {{-- ============ THE SIDE ============ --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">I can help you with</h2>
                    <ul class="list-unstyled mb-0 small">
                        <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle text-success" aria-hidden="true"></i> Choosing pieces by room, style and budget</li>
                        <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle text-success" aria-hidden="true"></i> Where to hang things, and which colours go together</li>
                        <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle text-success" aria-hidden="true"></i> How ordering and cash on delivery work</li>
                        <li class="d-flex gap-2"><i class="bi bi-check2-circle text-success" aria-hidden="true"></i> Finding the right free calculator for your room</li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-4">
                    <h2 class="h5 mb-2">Prefer to click?</h2>
                    <p class="small text-secondary">Pick a room, a style and a budget, and get a mood board.</p>
                    <a href="{{ route('helper') }}" class="btn btn-outline-primary rounded-pill"><i class="bi bi-magic me-1" aria-hidden="true"></i>Open the Décor Helper</a>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-2">Talk to a person</h2>
                    <p class="small text-secondary">For delivery times, returns or anything I do not know, please ask the shop directly.</p>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($wa)
                            <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Hello ' . $app . ', I have a question.') }}" target="_blank" rel="noopener noreferrer" class="btn sgh-btn-dark rounded-pill">
                                <i class="bi bi-whatsapp me-1" aria-hidden="true"></i>WhatsApp us
                            </a>
                        @endif
                        <a href="{{ route('contact') }}" class="btn btn-outline-primary rounded-pill">Contact form</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/assistant.js') }}" defer></script>
@endpush