<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaunchCheckTest extends TestCase
{
    use RefreshDatabase;

    // The settings a correct live site has
    private function goodSettings(): void
    {
        config([
            'app.env'        => 'production',
            'app.debug'      => false,
            'app.url'        => 'https://www.example.com',
            'app.key'        => 'base64:' . base64_encode(random_bytes(32)),
            'session.secure' => true,
            'mail.default'   => 'smtp',
        ]);
    }

    public function test_a_correct_live_setup_passes_with_no_blocking_problem(): void
    {
        $this->goodSettings();

        $this->artisan('launch:check')->assertExitCode(0);
    }

    public function test_each_critical_setting_makes_the_check_fail(): void
    {
        $bad = [
            'app.env'        => 'local',
            'app.debug'      => true,
            'app.url'        => 'http://www.example.com',
            'app.key'        => '',
            'session.secure' => false,
            'mail.default'   => 'log',
        ];

        foreach ($bad as $setting => $value) {
            $this->goodSettings();
            config([$setting => $value]);

            $this->artisan('launch:check')->assertExitCode(1);
        }
    }
}