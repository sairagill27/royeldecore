<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElegantThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_theme_colors_exist_and_the_gold_text_stays_dark_enough_to_read(): void
    {
        $css = file_get_contents(public_path('assets/css/style.css'));

        foreach (['63. ELEGANT THEME', '--sgh-emerald-deep', '--sgh-emerald', '--sgh-gold', '--sgh-champagne', 'PAGE-HEADERS-START', 'PAGE-HEADERS-END', '.sgh-shop-hero', '.sgh-footer-title', '.btn-primary', '.sgh-product-card'] as $needle) {
            $this->assertStringContainsString($needle, $css);
        }

        $this->assertMatchesRegularExpression('/--sgh-gold-deep:\s*#9A7428/', $css);
    }

    public function test_every_main_page_still_opens(): void
    {
        foreach (['/', '/shop', '/blog', '/tools', '/about', '/contact', '/cart', '/helper', '/assistant', '/recommended', '/login', '/register'] as $page) {
            $this->get($page)->assertOk();
        }
    }
}