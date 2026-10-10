@extends('layouts.app')

@section('title', 'Choose a new password | ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="sgh-auth-card mx-auto">

        <h1 class="h3 text-center mb-1">Choose a new password</h1>
        <p class="text-center text-secondary small mb-4">Pick a password you have not used before.</p>

        <form action="{{ route('password.store') }}" method="POST" novalidate>
            @csrf

            {{-- The secret token from the email link (goes back to the server with the form) --}}
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}"
                    class="form-control @error('email') is-invalid @enderror"
                    autocomplete="email" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">New password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        autocomplete="new-password" required autofocus>
                    <button type="button" class="btn sgh-eye" data-toggle-password="password"
                        aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
                </div>
                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                <div class="form-text">At least 8 characters, with letters and numbers.</div>
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label">Confirm new password</label>
                <div class="input-group">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        class="form-control" autocomplete="new-password" required>
                    <button type="button" class="btn sgh-eye" data-toggle-password="password_confirmation"
                        aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg rounded-pill w-100">Save new password</button>
        </form>

        <p class="text-center small mt-4 mb-0">
            <a href="{{ route('password.request') }}">Send me a new link</a>
        </p>
    </div>
</div>

{{-- Eye buttons: switch each box between hidden (dots) and visible (text) --}}
<script>
    document.querySelectorAll('[data-toggle-password]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = document.getElementById(btn.dataset.togglePassword);
            var show = input.type === 'password';

            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });
</script>
@endsection