<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageHeaderPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_header_styles_exist(): void
    {
        $css = file_get_contents(public_path('assets/css/style.css'));

        foreach (['61. PAGE HEADERS', '--sgh-head-glow', '--sgh-head-photo-width', '--sgh-head-photo-opacity', '.sgh-head-photo'] as $needle) {
            $this->assertStringContainsString($needle, $css);
        }
    }

    public function test_every_page_header_gets_the_warm_shade_but_the_home_page_has_no_photo_box(): void
    {
        Storage::fake('public');

        foreach (['/shop', '/blog', '/tools', '/helper', '/assistant', '/recommended'] as $page) {
            $this->get($page)->assertOk()->assertSee('sgh-head-glow', false);
        }

        $this->get('/')->assertOk()->assertDontSee('sgh-head-photo', false);
    }

    public function test_a_photo_placed_by_hand_appears_only_on_its_own_page(): void
    {
        Storage::fake('public');

        $dir = public_path('assets/images/headers');

        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            if (file_exists($dir . '/recommended.' . $ext)) {
                $this->markTestSkipped('A real recommended picture exists, so this test leaves it alone.');
            }
        }

        $this->get('/recommended')->assertOk()->assertDontSee('sgh-head-photo', false);

        $made = ! is_dir($dir);
        if ($made) {
            mkdir($dir, 0777, true);
        }
        $file = $dir . '/recommended.jpg';
        file_put_contents($file, 'not a real picture, only a stand-in for the test');

        try {
            $this->get('/recommended')->assertOk()
                ->assertSee('class="sgh-head-photo"', false)
                ->assertSee('assets/images/headers/recommended.jpg', false);

            $this->get('/helper')->assertOk()->assertDontSee('headers/recommended.jpg', false);
            $this->get('/')->assertOk()->assertDontSee('sgh-head-photo', false);
        } finally {
            @unlink($file);
            if ($made) {
                @rmdir($dir);
            }
        }
    }
}