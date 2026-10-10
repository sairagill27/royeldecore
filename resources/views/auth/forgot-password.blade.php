@extends('layouts.app')

@section('title', 'Forgot password | ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="sgh-auth-card mx-auto">

        <h1 class="h3 text-center mb-1">Forgot your password?</h1>
        <p class="text-center text-secondary small mb-4">
            Enter your email and we will send you a link to choose a new one.
        </p>

        <form action="{{ route('password.email') }}" method="POST" novalidate>
            @csrf

            <div class="mb-4">
                <label for="email" class="form-label">Email address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    class="form-control @error('email') is-invalid @enderror"
                    autocomplete="email" required autofocus>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-lg rounded-pill w-100">Send reset link</button>
        </form>

        <p class="text-center small mt-4 mb-0">
            <a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> Back to log in</a>
        </p>
    </div>
</div>
@endsection