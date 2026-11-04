@extends('layouts.app')

@section('title', 'Two-step login | ' . config('app.name'))

{{-- A private page: search engines are asked to skip it --}}
@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="container py-5">
    <div class="sgh-2fa-card mx-auto">
        <span class="sgh-2fa-icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
        <h1 class="h3 mb-2">Two-step login</h1>
        <p class="text-secondary">Open your authenticator app and type the 6-digit code for {{ config('app.name') }}.</p>

        {{-- @csrf = security token --}}
        <form method="POST" action="{{ route('admin.two-factor.verify') }}" novalidate>
            @csrf
            <label for="code" class="form-label visually-hidden">Code</label>
            <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="24" autofocus required placeholder="123456"
                   class="form-control form-control-lg text-center sgh-2fa-input @error('code') is-invalid @enderror">
            @error('code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            <div class="form-text mb-3">Lost your phone? Type one of your recovery codes instead.</div>

            <button type="submit" class="btn sgh-btn-dark rounded-pill w-100 py-2">Continue</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-link btn-sm">Log out</button>
        </form>
    </div>
</div>
@endsection