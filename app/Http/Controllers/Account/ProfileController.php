<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    // Show the profile page
    public function edit(Request $request)
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    // Save name and email
    public function update(Request $request)
    {
        $user = $request->user();

        // Emails are saved in lowercase
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        // SECURITY: every value is checked on the server.
        // The customer's own email is allowed to stay the same.
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        // role is never taken from the form
        $user->fill($data)->save();

        return back()->with('success', 'Your details were updated.');
    }

    // Change the password
    public function password(Request $request)
    {
        $user = $request->user();

        $request->validate([
            // "current_password" checks what they typed against the saved (hashed) password
            'current_password' => ['required', 'current_password'],
            'password'         => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(8)->letters()->numbers()
            ],
        ], [
            'current_password.current_password' => 'Your current password is not correct.',
            'password.different'                => 'Your new password must be different from the current one.',
        ]);

        $user->password = Hash::make($request->input('password'));   // stored hashed, as always
        $user->save();

        // Safety: log this account out of every OTHER device or browser
        // (sessions are stored in the database). This device stays logged in.
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('success', 'Your password was changed. Other devices were logged out.');
    }
}
