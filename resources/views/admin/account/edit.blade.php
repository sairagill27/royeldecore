@extends('layouts.admin')

@section('title', 'My account')
@section('heading', 'My account')

@section('content')

<div class="row g-3">
    <div class="col-xl-7">

        {{-- ============ NAME AND EMAIL ============ --}}
        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head">
                <div>
                    <h2>Your details</h2>
                    <p>The name and email you log in with. Your role is {{ ucfirst((string) $user->role) }}.</p>
                </div>
            </div>

            {{-- @csrf = security token. @method('PATCH') tells Laravel "this is an update". --}}
            <form method="POST" action="{{ route('admin.account.update') }}" novalidate>
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label for="acc-name" class="form-label">Name</label>
                    <input type="text" id="acc-name" name="name" value="{{ old('name', $user->name) }}" maxlength="100"
                           autocomplete="name" required class="form-control @error('name') is-invalid @enderror">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="acc-email" class="form-label">Email (you log in with this)</label>
                    <input type="email" id="acc-email" name="email" value="{{ old('email', $user->email) }}" maxlength="255"
                           autocomplete="email" required class="form-control @error('email') is-invalid @enderror">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="acc-email-password" class="form-label">
                        Current password <span class="text-secondary">(only needed when you change your email)</span>
                    </label>
                    <input type="password" id="acc-email-password" name="email_password" autocomplete="current-password"
                           class="form-control @error('email_password') is-invalid @enderror">
                    @error('email_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Changing your email also logs you out on your other devices.</div>
                </div>

                <button type="submit" class="btn btn-primary rounded-pill px-4">Save details</button>
            </form>
        </div>

        {{-- ============ PASSWORD ============ --}}
        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div>
                    <h2>Change your password</h2>
                    <p>At least 14 characters. A sentence of four unrelated words works well and is easy to remember.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.account.password') }}" novalidate>
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="acc-current" class="form-label">Current password</label>
                    <input type="password" id="acc-current" name="current_password" autocomplete="current-password" required
                           class="form-control @error('current_password') is-invalid @enderror">
                    @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="acc-new" class="form-label">New password</label>
                    <input type="password" id="acc-new" name="password" autocomplete="new-password" minlength="14" required
                           class="form-control @error('password') is-invalid @enderror">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="acc-confirm" class="form-label">Type the new password again</label>
                    <input type="password" id="acc-confirm" name="password_confirmation" autocomplete="new-password" minlength="14" required
                           class="form-control">
                </div>

                <button type="submit" class="btn btn-primary rounded-pill px-4">Change password</button>
            </form>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head"><div><h2>Two-step login</h2></div></div>
            @if (\App\Services\TwoFactor::isOn($user))
                <p class="mb-2"><span class="badge sgh-badge-ok"><i class="bi bi-shield-check me-1" aria-hidden="true"></i> On</span></p>
            @else
                <p class="mb-2"><span class="badge sgh-badge-low">Off</span></p>
            @endif
            <p class="small text-secondary mb-2">A code from your phone, after your password. It is the best protection for an admin account.</p>
            <a href="{{ route('admin.two-factor.setup') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">Manage two-step login</a>
        </div>

        <div class="sgh-panel">
            <div class="sgh-panel-head"><div><h2>Good to know</h2></div></div>
            <ul class="small text-secondary mb-0 ps-3">
                <li>Alerts about new orders, comments and reviews go to the address in your server settings (<code>ADMIN_EMAIL</code>), not necessarily to this email.</li>
                <li>Never share your password. Nobody from this shop's software will ever ask for it.</li>
            </ul>
        </div>
    </div>
</div>

@endsection