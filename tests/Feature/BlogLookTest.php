<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogLookTest extends TestCase
{
    use RefreshDatabase;

    // (The helper name must not clash with Laravel's own get(), post()... test methods.)
    private function makeArticle(array $o = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'user_id'      => $this->makeAdmin()->id,
            'title'        => 'A test post',
            'slug'         => 'post-' . Str::lower(Str::random(8)),
            'content'      => 'Some content.',
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ], $o));
    }

    public function test_the_blog_header_is_the_green_header_with_search_and_category_pills(): void
    {
        $category = BlogCategory::create(['name' => 'Bedroom', 'slug' => 'bedroom', 'sort_order' => 1]);
        $this->makeArticle(['blog_category_id' => $category->id]);

        $this->get('/blog')->assertOk()
            ->assertSee('class="sgh-tool-head sgh-shop-head sgh-blog-head"', false)
            ->assertSee('sgh-tool-leaves', false)
            ->assertSee('The journal')
            ->assertSee('id="blog-q"', false)
            ->assertSee('class="sgh-pill active"', false)
            ->assertSee('href="' . route('blog.category', 'bedroom') . '"', false);
    }

    public function test_a_category_page_keeps_the_green_header_and_marks_its_pill(): void
    {
        $category = BlogCategory::create(['name' => 'Bedroom', 'slug' => 'bedroom', 'sort_order' => 1]);
        $this->makeArticle(['blog_category_id' => $category->id]);

        $this->get('/blog/category/bedroom')->assertOk()
            ->assertSee('sgh-blog-head', false)
            ->assertSee('Bedroom')
            ->assertSee('aria-current="page"', false);
    }

    public function test_the_featured_article_is_a_compact_card_inside_the_narrow_column(): void
    {
        $this->makeArticle(['title' => 'Newest One', 'published_at' => now()->subDay()]);
        $this->makeArticle(['title' => 'Older One', 'published_at' => now()->subDays(4)]);

        $html = $this->get('/blog')->assertOk()
            ->assertSee('sgh-featured-post', false)
            ->assertSee('sgh-featured-flag', false)
            ->assertSee('Featured article')
            ->assertSee('Latest articles')
            ->getContent();

        $this->assertStringContainsString('sgh-blog-wrap', $html);
        $this->assertSame(1, substr_count($html, 'class="sgh-featured-post'));
    }

    public function test_an_empty_blog_says_so(): void
    {
        $this->get('/blog')->assertOk()
            ->assertSee('No articles yet')
            ->assertDontSee('sgh-featured-post', false);
    }

    public function test_search_words_are_never_run_as_code(): void
    {
        $this->makeArticle();

        $this->get('/blog?q=' . urlencode('<script>alert(1)</script>'))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('No articles found');
    }

    public function test_the_blog_page_has_exactly_one_h1(): void
    {
        $this->makeArticle();

        $this->assertSame(1, substr_count($this->get('/blog')->assertOk()->getContent(), '<h1'));
    }

    public function test_the_article_page_uses_the_green_header_and_keeps_one_h1(): void
    {
        $this->makeArticle(['slug' => 'live-one', 'title' => 'Live One']);

        $html = $this->get('/blog/live-one')->assertOk()
            ->assertSee('class="sgh-tool-head sgh-article-hero"', false)
            ->assertSee('sgh-tool-leaves', false)
            ->assertSee('Live One')
            ->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_an_article_title_cannot_run_as_code_in_the_new_header(): void
    {
        $this->makeArticle(['slug' => 'evil', 'title' => '<script>alert(1)</script>']);

        $this->get('/blog/evil')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/blog')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}