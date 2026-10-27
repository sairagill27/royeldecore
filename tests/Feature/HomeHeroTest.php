<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $name, string $slug, bool $active = true, int $order = 1): Category
    {
        return Category::create(['name' => $name, 'slug' => $slug, 'is_active' => $active, 'sort_order' => $order]);
    }

    public function test_the_hero_has_the_new_wording_and_buttons(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Premium Home Décor')
            ->assertSee('Beautiful Spaces Start with the Right')
            ->assertSee('Transform your home with unique décor pieces, designed to bring warmth, style and inspiration to every corner.')
            ->assertSee('Shop Now')
            ->assertSee('Explore Ideas')
            ->assertSee('href="' . route('shop') . '"', false)
            ->assertSee('href="' . route('blog.index') . '"', false);
    }

    public function test_the_hero_has_a_search_box_that_uses_the_shop_search(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('action="' . route('search') . '"', false)
            ->assertSee('id="hero-q"', false)
            ->assertSee('name="q"', false);
    }

    public function test_the_hero_shows_real_visible_categories_only_and_at_most_four(): void
    {
        $this->category('Zebra Lamps', 'zebra-lamps', true, 1);
        $this->category('Secret Room', 'secret-room', false, 2);
        foreach (['Alpha', 'Beta', 'Gamma', 'Delta'] as $i => $name) {
            $this->category($name . ' Pieces', strtolower($name), true, 10 + $i);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(
            '<a href="' . route('shop', ['category' => 'zebra-lamps']) . '" class="sgh-banner-chip">Zebra Lamps</a>',
            $html
        );
        $this->assertStringNotContainsString('class="sgh-banner-chip">Secret Room', $html);
        $this->assertSame(4, substr_count($html, 'class="sgh-banner-chip"'));
    }

    public function test_the_hero_makes_no_promises_the_shop_has_not_written_down(): void
    {
        $this->get('/')->assertOk()
            ->assertDontSee('Fast Delivery')
            ->assertDontSee('Easy Returns')
            ->assertDontSee('Secure Shopping')
            ->assertDontSee('Premium Quality')
            ->assertDontSee('Free delivery');
    }

    public function test_the_leaf_drawing_and_the_photo_area_are_on_the_page(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('sgh-banner-leaves', false)
            ->assertSee('id="sgh-branch"', false)
            ->assertSee('sgh-banner-photo', false);
    }
}