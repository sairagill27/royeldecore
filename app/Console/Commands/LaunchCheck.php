<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class LaunchCheck extends Command
{
    protected $signature = 'launch:check';

    protected $description = 'Checks the settings that must be right before the site goes online';

    private int $fails = 0;
    private int $warns = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('<options=bold>Launch check</>');
        $this->line('FAIL = fix before launch.  WARN = please look at it.');
        $this->newLine();

        // ---------- settings ----------
        $this->line('<options=bold>Settings (.env)</>');

        $this->result(
            config('app.env') === 'production',
            'APP_ENV is "production"',
            'It is "' . config('app.env') . '". Set APP_ENV=production on the live server.'
        );
        $this->result(
            config('app.debug') === false,
            'APP_DEBUG is false',
            'Debug mode shows secrets to visitors. Set APP_DEBUG=false.'
        );
        $this->result(
            str_starts_with((string) config('app.url'), 'https://'),
            'APP_URL starts with https://',
            'It is "' . config('app.url') . '". Use your real https:// address.'
        );
        $this->result(
            filled(config('app.key')),
            'APP_KEY is set',
            'Run: php artisan key:generate  (do this on the server, so the live key is never the one from your PC).'
        );
        $this->result(
            config('session.secure') === true,
            'Session cookies are HTTPS-only (SESSION_SECURE_COOKIE=true)',
            'Add SESSION_SECURE_COOKIE=true to .env on the live server.'
        );
        $this->result(
            ! in_array((string) config('mail.default'), ['log', 'array'], true),
            'Real email sending is set up (MAIL_MAILER)',
            'MAIL_MAILER is "' . config('mail.default') . '", so emails only go to a log file.'
        );

        $connection = config('database.default');
        $db         = config('database.connections.' . $connection, []);

        if (($db['driver'] ?? '') === 'sqlite') {
            $this->result(true, 'Database user is not "root"', '');
            $this->result(true, 'Database user has a password', '');
        } else {
            $this->result(
                ($db['username'] ?? '') !== 'root',
                'Database user is not "root"',
                'Create a separate MySQL user for this site, with rights only to its own database.'
            );
            $this->result(
                filled($db['password'] ?? null),
                'Database user has a password',
                'Set DB_PASSWORD. An empty password is only acceptable on your own PC.'
            );
        }

        $this->result(
            blank(config('shop.admin_password')),
            'ADMIN_PASSWORD is removed from .env',
            'After the admin account exists, delete the ADMIN_PASSWORD line. Keep the password only in your head or a password manager.',
            false
        );

        // ---------- content and files ----------
        $this->newLine();
        $this->line('<options=bold>Content and files</>');

        $this->result(
            config('app.name') !== 'Laravel',
            'The site has its own name',
            'APP_NAME is still "Laravel".',
            false
        );
        $this->result(
            file_exists(public_path('storage')),
            'Uploaded photos are reachable (storage link)',
            'Run: php artisan storage:link',
            false
        );
        $this->result(
            file_exists(public_path('assets/images/logo.png')),
            'The logo file exists',
            'Add public/assets/images/logo.png.',
            false
        );
        $this->result(
            collect(['hero.webp', 'hero.jpg', 'hero.jpeg', 'hero.png', 'hero.jpg.jfif', 'hero.jfif'])
                ->contains(fn ($file) => file_exists(public_path('assets/images/' . $file))),
            'The home page photo exists',
            'Add public/assets/images/hero.webp (or .jpg).',
            false
        );
        $this->result(
            filled(config('contact.email')) || filled(config('contact.phone')) || filled(config('contact.whatsapp')),
            'At least one public way to contact the shop is set',
            'Fill CONTACT_EMAIL, CONTACT_PHONE or CONTACT_WHATSAPP in .env. Shoppers expect it.',
            false
        );

        $todo = 0;
        foreach (File::allFiles(resource_path('views')) as $file) {
            $todo += substr_count($file->getContents(), 'sgh-todo');
        }
        $this->result(
            $todo === 0,
            'No yellow placeholder marks are left in the pages',
            $todo . ' placeholder mark(s) remain (legal pages, About). Fill them in, and have the legal pages reviewed.',
            false
        );

        $sample = 0;
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'description')) {
            $sample = DB::table('products')->where('description', 'like', 'Sample description%')->count();
        }
        $this->result(
            $sample === 0,
            'No sample products are left',
            $sample . ' sample product(s) still have the placeholder description. Replace or delete them.',
            false
        );

        // ---------- summary ----------
        $this->newLine();
        if ($this->fails > 0) {
            $this->line('<fg=red;options=bold>' . $this->fails . ' problem(s) to fix before launch</>, ' . $this->warns . ' warning(s).');
        } else {
            $this->line('<fg=green;options=bold>No blocking problems.</> ' . $this->warns . ' warning(s).');
        }
        $this->newLine();

        return $this->fails > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function result(bool $pass, string $label, string $fix, bool $critical = true): void
    {
        if ($pass) {
            $this->line('  <fg=green>PASS</>  ' . $label);

            return;
        }

        if ($critical) {
            $this->fails++;
            $this->line('  <fg=red>FAIL</>  ' . $label);
        } else {
            $this->warns++;
            $this->line('  <fg=yellow>WARN</>  ' . $label);
        }

        $this->line('        ' . $fix);
    }
}