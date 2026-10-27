<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(string $name, string $slug, bool $active = true, int $order = 1): Category
    {
        return Category::create(['name' => $name, 'slug' => $slug, 'is_active' => $active, 'sort_order' => $order]);
    }

    // Makes a blog article. (The name must NOT be "post": Laravel's TestCase already has a post() method.)
    private function makeArticle(string $title, string $slug, array $overrides = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'user_id'      => $this->makeAdmin()->id,
            'title'        => $title,
            'slug'         => $slug,
            'content'      => 'Some content.',
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    public function test_the_hero_has_the_wording_and_buttons(): void
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

    public function test_the_hero_has_no_search_box(): void
    {
        $this->get('/')->assertOk()
            ->assertDontSee('id="hero-q"', false)
            ->assertDontSee('sgh-banner-search', false);
    }

    public function test_the_hero_shows_the_newest_live_article(): void
    {
        $this->makeArticle('Older Story', 'older', ['published_at' => now()->subDays(5)]);
        $this->makeArticle('Newest Story', 'newest', ['published_at' => now()->subDay()]);
        $this->makeArticle('Draft Story', 'draft', ['status' => 'draft', 'published_at' => null]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('From the journal', $html);
        $this->assertStringContainsString('<a href="' . route('blog.show', 'newest') . '" class="sgh-banner-feature">', $html);
        $this->assertStringNotContainsString('Draft Story', $html);
    }

    public function test_the_journal_strip_is_left_out_when_there_is_no_article(): void
    {
        $this->get('/')->assertOk()->assertDontSee('class="sgh-banner-feature"', false);
    }

    public function test_a_title_in_the_journal_strip_cannot_run_as_code(): void
    {
        $this->makeArticle('<script>alert(1)</script>', 'evil');

        $this->get('/')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_the_hero_shows_real_visible_categories_only_and_at_most_four(): void
    {
        $this->makeCategory('Zebra Lamps', 'zebra-lamps', true, 1);
        $this->makeCategory('Secret Room', 'secret-room', false, 2);
        foreach (['Alpha', 'Beta', 'Gamma', 'Delta'] as $i => $name) {
            $this->makeCategory($name . ' Pieces', strtolower($name), true, 10 + $i);
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