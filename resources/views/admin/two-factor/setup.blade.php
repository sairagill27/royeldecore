@extends('layouts.admin')

@section('title', 'Two-step login')
@section('heading', 'Two-step login')

@section('content')

<div class="row">
    <div class="col-xl-8">
        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div>
                    <h2>Turn on two-step login</h2>
                    <p>After your password, the admin area will also ask for a 6-digit code from your phone.</p>
                </div>
            </div>

            @if ($required)
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    This shop requires two-step login for everyone who uses the admin area.
                </div>
            @endif

            <ol class="sgh-2fa-steps">
                <li>
                    <strong>Install an authenticator app</strong> on your phone, for example Google Authenticator,
                    Microsoft Authenticator, Authy or 2FAS. Set your phone's clock to automatic.
                </li>
                <li>
                    <strong>Add this account to the app.</strong> Choose "Enter a setup key" and type this key.
                    Set the type to "time-based".
                    <div class="sgh-2fa-secret" aria-label="Setup key">{{ $grouped }}</div>
                    <p class="small text-secondary mb-0">
                        Account name: {{ auth()->user()->email }}.
                        On your phone? <a href="{{ $uri }}">Tap here to open your authenticator app</a>.
                    </p>
                </li>
                <li>
                    <strong>Type the 6-digit code</strong> the app shows, to check it works.

                    {{-- @csrf = security token --}}
                    <form method="POST" action="{{ route('admin.two-factor.enable') }}" class="mt-2" novalidate>
                        @csrf
                        <div class="d-flex flex-wrap gap-2 align-items-start">
                            <div>
                                <label for="code" class="visually-hidden">6-digit code</label>
                                <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                                       maxlength="10" placeholder="123456" required
                                       class="form-control sgh-2fa-input-sm @error('code') is-invalid @enderror">
                                @error('code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">Turn on</button>
                        </div>
                    </form>
                </li>
            </ol>

            <p class="small text-secondary mb-0">
                After this you will get 8 recovery codes. Keep them somewhere safe, such as a password manager.
                Each one works once, if you lose your phone.
            </p>
        </div>
    </div>
</div>

@endsection