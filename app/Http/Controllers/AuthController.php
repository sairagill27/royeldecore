<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ---------- REGISTER ----------

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Emails are saved in lowercase so "A@x.com" and "a@x.com" are the same account
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        // SECURITY: every value is checked on the server
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // at least 8 characters, with letters and numbers; must match "password_confirmation"
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        // role is NOT taken from the form: new accounts are always customers
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),   // hashed, never plain text
        ]);

        Auth::login($user);
        $request->session()->regenerate();   // new session ID after login

        return redirect()->route('account')
            ->with('success', 'Welcome! Your account has been created.')
            ->with('alert_title', 'Account created');
    }

    // ---------- LOGIN ----------

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = Str::lower($credentials['email']);

        // RATE LIMIT: max 5 wrong attempts per minute for this email + IP address
        $key = $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // is_active = true: blocked accounts cannot log in
        $ok = Auth::attempt(
            ['email' => $email, 'password' => $credentials['password'], 'is_active' => true],
            $request->boolean('remember')
        );

        if (! $ok) {
            RateLimiter::hit($key, 60);   // count the failed attempt for 60 seconds
            // Same message for "wrong email" and "wrong password" (does not reveal which accounts exist)
            throw ValidationException::withMessages([
                'email' => 'These details do not match our records.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();   // new session ID after login

        // "intended" = go back to the page they wanted (e.g. checkout), else the account page
        return redirect()->intended(route('account'))
            ->with('success', 'Welcome back, ' . Str::before($request->user()->name, ' ') . '!')
            ->with('alert_title', 'Login successful');
    }

    // ---------- LOGOUT ----------

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();       // destroys the old session
        $request->session()->regenerateToken();  // new CSRF token

        return redirect('/')
            ->with('success', 'You have been logged out. See you soon!')
            ->with('alert_title', 'Logged out');
    }
}
