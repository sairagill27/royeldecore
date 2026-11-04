<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

// "My account" inside the admin panel: change your own name, email and password.
class AccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.account.edit', ['user' => $request->user()]);
    }

    // Save name and email
    public function update(Request $request)
    {
        $user = $request->user();

        // Emails are saved in lowercase. Anything that is not plain text counts as empty.
        $raw   = $request->input('email');
        $email = is_string($raw) ? Str::lower(trim($raw)) : '';
        $request->merge(['email' => $email]);

        $changingEmail = $email !== Str::lower((string) $user->email);

        // SECURITY: every value is checked on the server.
        $rules = [
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];

        // A new email address needs the current password
        if ($changingEmail) {
            $rules['email_password'] = ['required', 'current_password'];
        }

        $data = $request->validate($rules, [
            'email_password.required'         => 'Please type your current password to change your email address.',
            'email_password.current_password' => 'Your current password is not correct.',
        ]);

        // role is never taken from the form
        $user->fill(['name' => $data['name'], 'email' => $data['email']])->save();

        if ($changingEmail) {
            $this->logOutOtherDevices($request, $user->id);
        }

        return back()
            ->with('success', $changingEmail
                ? 'Your details were updated. Use your new email the next time you log in.'
                : 'Your details were updated.')
            ->with('alert_title', 'Saved');
    }

    // Change the password
    public function password(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', 'different:current_password', Password::min(14)->letters()],
        ], [
            'current_password.current_password' => 'Your current password is not correct.',
            'password.different'                => 'Your new password must be different from the current one.',
            'password.min'                      => 'Your new password must be at least 14 characters. A sentence of four unrelated words works well.',
        ]);

        $user->password = Hash::make($request->input('password'));   // stored hashed, as always
        $user->setRememberToken(Str::random(60));                     // "remember me" logins stop working
        $user->save();

        $this->logOutOtherDevices($request, $user->id);

        return back()
            ->with('success', 'Your password was changed. Other devices were logged out.')
            ->with('alert_title', 'Password changed');
    }

    // Every OTHER device or browser of this account is logged out (sessions are stored in the database)
    private function logOutOtherDevices(Request $request, int $userId): void
    {
        DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}