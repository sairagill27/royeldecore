@extends('layouts.admin')

@section('title', 'Two-step login')
@section('heading', 'Two-step login')

@section('content')

<div class="row g-3">
    <div class="col-xl-8">

        {{-- Recovery codes: only right after turning it on, or after making new ones --}}
        @if (! empty($codes))
            <div class="sgh-panel mb-3 sgh-2fa-print">
                <div class="sgh-panel-head">
                    <div>
                        <h2>Your recovery codes</h2>
                        <p>Save these now. They are shown only once. Each code works one time.</p>
                    </div>
                </div>
                <ul class="sgh-2fa-codes">
                    @foreach ($codes as $recovery)
                        <li>{{ $recovery }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="window.print()">
                    <i class="bi bi-printer me-1" aria-hidden="true"></i> Print
                </button>
            </div>
        @endif

        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head">
                <div>
                    <h2>Two-step login is on</h2>
                    <p>You have {{ $left }} recovery {{ \Illuminate\Support\Str::plural('code', $left) }} left.</p>
                </div>
                <span class="badge sgh-badge-ok"><i class="bi bi-shield-check me-1" aria-hidden="true"></i> On</span>
            </div>
            <p class="mb-0 text-secondary small">
                The admin area asks for a code after your password. If you are running low on recovery codes, make new ones below.
            </p>
        </div>

        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head"><div><h2>Make new recovery codes</h2><p>The old codes stop working.</p></div></div>

            <form method="POST" action="{{ route('admin.two-factor.recovery') }}" novalidate>
                @csrf
                <label for="rc-password" class="form-label">Your password</label>
                <div class="d-flex flex-wrap gap-2 align-items-start">
                    <div>
                        <input type="password" id="rc-password" name="password" autocomplete="current-password" required
                               class="form-control @error('recovery') is-invalid @enderror">
                        @error('recovery') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-outline-primary rounded-pill px-4">Make new codes</button>
                </div>
            </form>
        </div>

        <div class="sgh-panel">
            <div class="sgh-panel-head"><div><h2>Turn off two-step login</h2><p>This makes your account less safe.</p></div></div>

            @if ($required)
                <p class="small text-secondary">This shop requires two-step login, so you will be asked to set it up again.</p>
            @endif

            <form method="POST" action="{{ route('admin.two-factor.disable') }}" novalidate>
                @csrf
                <div class="row g-2">
                    <div class="col-sm-6">
                        <label for="off-password" class="form-label">Your password</label>
                        <input type="password" id="off-password" name="password" autocomplete="current-password" required
                               class="form-control @error('disable') is-invalid @enderror">
                    </div>
                    <div class="col-sm-6">
                        <label for="off-code" class="form-label">A fresh code or a recovery code</label>
                        <input type="text" id="off-code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="24" required
                               class="form-control @error('disable') is-invalid @enderror">
                    </div>
                </div>
                @error('disable') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                <button type="submit" class="btn btn-outline-danger rounded-pill px-4 mt-3"
                        onclick="return confirm('Turn off two-step login?')">Turn off</button>
            </form>
        </div>
    </div>
</div>

@endsection