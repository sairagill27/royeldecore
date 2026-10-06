@extends('layouts.app')

@section('title', 'Create account | ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="sgh-auth-card mx-auto">

        <h1 class="h3 text-center mb-1">Create your account</h1>
        <p class="text-center text-secondary small mb-4">Save your orders and shop faster.</p>

        {{-- @csrf = security token. Without it, Laravel rejects the form. --}}
        <form action="{{ url('/register') }}" method="POST" novalidate>
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Full name</label>
                {{-- old('name') keeps what was typed if there is an error --}}
                <input type="text" id="name" name="name" value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror"
                    autocomplete="name" required autofocus>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    class="form-control @error('email') is-invalid @enderror"
                    autocomplete="email" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    autocomplete="new-password" required>
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">At least 8 characters, with letters and numbers.</div>
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                    class="form-control" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg rounded-pill w-100">Create account</button>
        </form>

        <p class="text-center small mt-4 mb-0">
            Already have an account? <a href="{{ url('/login') }}">Log in</a>
        </p>
    </div>
</div>
@endsection