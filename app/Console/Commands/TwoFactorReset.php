<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TwoFactorReset extends Command
{
    protected $signature = 'two-factor:reset {email : The email address of the account}';

    protected $description = 'Turns two-step login off for one account (for someone who lost their phone and recovery codes)';

    public function handle(): int
    {
        $user = User::where('email', (string) $this->argument('email'))->first();

        if (! $user) {
            $this->error('No account has that email address.');

            return self::FAILURE;
        }

        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_confirmed_at'   => null,
            'two_factor_recovery_codes' => null,
            'two_factor_last_step'      => null,
        ])->save();

        $this->info('Two-step login is now off for ' . $user->email . '. They can turn it on again in the admin panel.');

        return self::SUCCESS;
    }
}