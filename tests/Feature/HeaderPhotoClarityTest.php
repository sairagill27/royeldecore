<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderPhotoClarityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_clearer_header_photo_settings_exist(): void
    {
        $css = file_get_contents(public_path('assets/css/style.css'));

        foreach (['64. HEADER PHOTO', '--sgh-head-fade', '--sgh-head-fade-bottom', '--sgh-head-focus', '.sgh-head-photo ~ .sgh-tool-leaves', 'mask-composite: intersect'] as $needle) {
            $this->assertStringContainsString($needle, $css);
        }

        $this->assertMatchesRegularExpression('/--sgh-head-photo-width:\s*62%/', $css);
    }
}