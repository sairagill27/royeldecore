@extends('layouts.app')

@section('title', 'Log in | ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="sgh-auth-card mx-auto">

        <h1 class="h3 text-center mb-1">Welcome back</h1>
        <p class="text-center text-secondary small mb-4">Log in to see your orders and account.</p>

        <form action="{{ url('/login') }}" method="POST" novalidate>
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       autocomplete="email" required autofocus>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>

                {{-- Password box + eye button. data-toggle-password says which box the button controls. --}}
                <div class="input-group">
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           autocomplete="current-password" required>
                    <button type="button" class="btn sgh-eye" data-toggle-password="password"
                            aria-label="Show password" aria-pressed="false">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                <label class="form-check-label small" for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg rounded-pill w-100">Log in</button>
        </form>

        <p class="text-center small mt-4 mb-0">
            New here? <a href="{{ url('/register') }}">Create an account</a>
        </p>
    </div>
</div>

{{-- Eye button: switches the box between hidden (dots) and visible (text) --}}
<script>
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.togglePassword);
            var show  = input.type === 'password';

            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });
</script>
@endsection