<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactor;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorController extends Controller
{
    private const MAX_TRIES    = 5;     // wrong codes allowed...
    private const LOCK_SECONDS = 600;   // ...within this many seconds

    // ---------- turn it on or off (inside the admin area) ----------

    public function setup(Request $request)
    {
        $user = $request->user();

        if (TwoFactor::isOn($user)) {
            $left = json_decode((string) $user->two_factor_recovery_codes, true);

            return $this->noStore(response()->view('admin.two-factor.manage', [
                'codes'    => session('recovery_codes'),          // only right after turning on or making new ones
                'left'     => is_array($left) ? count($left) : 0,
                'required' => (bool) config('security.two_factor_required'),
            ]));
        }

        // The key is made once and kept in the session until the first code proves the app works
        $secret = $request->session()->get('two_factor_pending_secret');
        if (! is_string($secret) || $secret === '') {
            $secret = TwoFactor::generateSecret();
            $request->session()->put('two_factor_pending_secret', $secret);
        }

        return $this->noStore(response()->view('admin.two-factor.setup', [
            'grouped'  => trim(chunk_split($secret, 4, ' ')),
            'uri'      => TwoFactor::uri($secret, (string) $user->email, config('app.name')),
            'required' => (bool) config('security.two_factor_required'),
        ]));
    }

    public function enable(Request $request)
    {
        $user = $request->user();

        if (TwoFactor::isOn($user)) {
            return redirect()->route('admin.two-factor.setup');
        }

        $secret = $request->session()->get('two_factor_pending_secret');
        $code   = $request->input('code');
        $step   = (is_string($secret) && is_string($code)) ? TwoFactor::verify($secret, $code) : null;

        if ($step === null) {
            return redirect()->route('admin.two-factor.setup')
                ->withErrors(['code' => 'That code is not right. Check that it is the current code, and that your phone clock is set automatically.']);
        }

        $codes = TwoFactor::newRecoveryCodes();

        // forceFill: these columns can never be set from a form
        $user->forceFill([
            'two_factor_secret'         => Crypt::encryptString($secret),
            'two_factor_confirmed_at'   => now(),
            'two_factor_recovery_codes' => TwoFactor::hashCodes($codes),
            'two_factor_last_step'      => $step,
        ])->save();

        $request->session()->forget('two_factor_pending_secret');
        $request->session()->put('two_factor_ok', $user->id);

        return redirect()->route('admin.two-factor.setup')
            ->with('recovery_codes', $codes)
            ->with('success', 'Two-step login is now on. Please save your recovery codes.')
            ->with('alert_title', 'Two-step login on');
    }

    public function disable(Request $request)
    {
        $user     = $request->user();
        $password = $request->input('password');
        $code     = $request->input('code');

        $ok = is_string($password) && is_string($code)
            && Hash::check($password, (string) $user->password)
            && $this->acceptCode($user, $code);

        if (! $ok) {
            return redirect()->route('admin.two-factor.setup')
                ->withErrors(['disable' => 'The password or the code is not right. Use a fresh code, not one you have already used.']);
        }

        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_confirmed_at'   => null,
            'two_factor_recovery_codes' => null,
            'two_factor_last_step'      => null,
        ])->save();

        $request->session()->forget('two_factor_ok');

        return redirect()->route('admin.two-factor.setup')
            ->with('success', 'Two-step login is now off.')
            ->with('alert_title', 'Two-step login off');
    }

    public function regenerate(Request $request)
    {
        $user     = $request->user();
        $password = $request->input('password');

        if (! is_string($password) || ! Hash::check($password, (string) $user->password)) {
            return redirect()->route('admin.two-factor.setup')
                ->withErrors(['recovery' => 'The password is not right.']);
        }

        $codes = TwoFactor::newRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => TwoFactor::hashCodes($codes)])->save();

        return redirect()->route('admin.two-factor.setup')
            ->with('recovery_codes', $codes)
            ->with('success', 'New recovery codes were made. The old ones no longer work.')
            ->with('alert_title', 'New recovery codes');
    }

    // ---------- the code page (outside the two-factor check) ----------

    public function challenge(Request $request)
    {
        $user = $request->user();

        if (! TwoFactor::isOn($user) || (int) $request->session()->get('two_factor_ok') === (int) $user->id) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.two-factor.challenge');
    }

    public function verify(Request $request)
    {
        $user = $request->user();

        if (! TwoFactor::isOn($user)) {
            return redirect()->route('admin.dashboard');
        }

        $key = '2fa:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, self::MAX_TRIES)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            return redirect()->route('admin.two-factor.challenge')
                ->withErrors(['code' => 'Too many wrong codes. Please try again in ' . $minutes . ' ' . Str::plural('minute', $minutes) . '.']);
        }

        $input = $request->input('code');

        if (! is_string($input) || trim($input) === '' || mb_strlen($input) > 40 || ! $this->acceptCode($user, $input)) {
            RateLimiter::hit($key, self::LOCK_SECONDS);

            return redirect()->route('admin.two-factor.challenge')
                ->withErrors(['code' => 'That code is not right. Check the 6 digits in your app, or use a recovery code.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('two_factor_ok', $user->id);

        // Back to the page they wanted (only addresses inside the admin area are accepted)
        $intended = $request->session()->pull('two_factor_intended');
        $target   = (is_string($intended) && str_starts_with($intended, url('/admin'))) ? $intended : route('admin.dashboard');

        return redirect()->to($target);
    }

    // ---------- helpers ----------

    // A 6-digit app code (each one works only once) or a recovery code (each one works only once)
    private function acceptCode(User $user, string $input): bool
    {
        $digits = preg_replace('/\s+/', '', trim($input));

        if (preg_match('/^\d{6}$/', $digits)) {
            $secret = $this->secretOf($user);
            if ($secret === null) {
                return false;
            }

            $step = TwoFactor::verify($secret, $digits);
            if ($step === null || $step <= (int) $user->two_factor_last_step) {
                return false;
            }

            $user->forceFill(['two_factor_last_step' => $step])->save();

            return true;
        }

        $left = TwoFactor::consumeRecovery($user->two_factor_recovery_codes, $input);
        if ($left === null) {
            return false;
        }

        $user->forceFill(['two_factor_recovery_codes' => json_encode($left)])->save();

        return true;
    }

    // The saved key, or null if it cannot be read (for example after the app key was changed)
    private function secretOf(User $user): ?string
    {
        try {
            return Crypt::decryptString((string) $user->two_factor_secret);
        } catch (DecryptException) {
            return null;
        }
    }

    // Pages that show a key or recovery codes must never be saved by the browser
    private function noStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}