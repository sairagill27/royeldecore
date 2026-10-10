<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    // Page 1: "Forgot your password?" form
    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    // Sends the reset email
    public function sendLink(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        // is_active = true: blocked accounts get no link.
        // The result is ignored ON PURPOSE. The answer is always the same,
        // so nobody can find out which emails have an account.
        Password::sendResetLink([
            'email'     => $request->input('email'),
            'is_active' => true,
        ]);

        return back()->with('success', 'If an account exists for that email, we sent a password reset link. Check your inbox and your spam folder.');
    }

    // Page 2: the page the email link opens
    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    // Saves the new password
    public function reset(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        // SECURITY: every value is checked on the server
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),   // stored hashed, as always
                    'remember_token' => Str::random(60),         // ends any "remember me" login
                ])->save();

                // Log this account out on every device
                DB::table('sessions')->where('user_id', $user->id)->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Your password was changed. You can log in now.');
        }

        // Wrong, used or expired link. One message for all of them.
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'This reset link is not valid or has expired. Please request a new one.']);
    }
}
