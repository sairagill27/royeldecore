<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email    = config('shop.admin_email');
        $password = config('shop.admin_password');

        // Stop with a clear message if .env is not filled in
        if (! $email || ! $password) {
            $this->command->error('Set ADMIN_EMAIL and ADMIN_PASSWORD in .env first.');
            return;
        }

        // role is not in $fillable (on purpose), so we set the fields one by one here
        $user           = User::firstOrNew(['email' => strtolower($email)]);
        $user->name     = $user->name ?: 'Administrator';
        $user->password = Hash::make($password);   // stored hashed, as always
        $user->role     = 'admin';
        $user->is_active = true;
        $user->save();

        $this->command->info('Admin account is ready: ' . $user->email);
    }
}
