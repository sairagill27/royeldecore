@extends('layouts.app')

@section('title', 'Profile and password | ' . config('app.name'))

@section('content')
<div class="container py-4 py-lg-5">

    <h1 class="sgh-section-title mb-1">Profile and password</h1>
    <p class="text-secondary mb-4">
        <a href="{{ route('account') }}"><i class="bi bi-arrow-left"></i> Back to my account</a>
    </p>

    <div class="row g-4">

        {{-- ============ YOUR DETAILS ============ --}}
        <div class="col-lg-6">
            <div class="sgh-card h-100">
                <h2 class="sgh-card-title"><span><i class="bi bi-person"></i></span> Your details</h2>

                {{-- @csrf = security token. @method('PATCH') tells Laravel "this is an update". --}}
                <form action="{{ route('account.profile.update') }}" method="POST" novalidate>
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="name" class="form-label">Full name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                            class="form-control @error('name') is-invalid @enderror"
                            autocomplete="name" maxlength="100" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                            class="form-control @error('email') is-invalid @enderror"
                            autocomplete="email" maxlength="255" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save details</button>
                </form>
            </div>
        </div>

        {{-- ============ CHANGE PASSWORD ============ --}}
        <div class="col-lg-6">
            <div class="sgh-card h-100">
                <h2 class="sgh-card-title"><span><i class="bi bi-shield-lock"></i></span> Change password</h2>

                {{-- @method('PUT') tells Laravel "this is a replacement" --}}
                <form action="{{ route('account.password') }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current password</label>
                        <div class="input-group">
                            <input type="password" id="current_password" name="current_password"
                                class="form-control @error('current_password') is-invalid @enderror"
                                autocomplete="current-password" required>
                            <button type="button" class="btn sgh-eye" data-toggle-password="current_password"
                                aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
                        </div>
                        @error('current_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                autocomplete="new-password" required>
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

                    <button type="submit" class="btn btn-primary rounded-pill px-4">Change password</button>
                </form>
            </div>
        </div>
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