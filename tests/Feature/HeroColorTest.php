<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_hero_color_settings_exist_and_the_hero_markup_is_untouched(): void
    {
        $css = file_get_contents(public_path('assets/css/style.css'));

        foreach (['60. HERO COLORS', '--sgh-hero-cream', '--sgh-hero-sand', '--sgh-hero-sage', '--sgh-hero-glow', '--sgh-hero-gold-text', '.sgh-banner-feature-icon'] as $needle) {
            $this->assertStringContainsString($needle, $css);
        }

        $this->get('/')->assertOk()
            ->assertSee('class="sgh-banner"', false)
            ->assertSee('sgh-banner-eyebrow', false)
            ->assertSee('Premium Home')
            ->assertSee('sgh-btn-ghost', false);
    }
}