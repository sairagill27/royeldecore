<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogSeoTest extends TestCase
{
    use RefreshDatabase;

    // Makes a live post. Pass values to change any of them.
    private function makePost(array $overrides = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'user_id'      => $this->makeAdmin()->id,
            'title'        => 'A test post',
            'slug'         => Str::lower(Str::random(10)),
            'content'      => 'Some content.',
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    // ---------- robots.txt ----------

    public function test_robots_blocks_everything_unless_the_site_is_in_production(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /')->assertDontSee('Sitemap:');
    }

    public function test_robots_in_production_hides_private_areas_and_points_to_the_sitemap(): void
    {
        $this->app['env'] = 'production';

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /checkout')
            ->assertSee('Sitemap: ' . route('sitemap'));
    }

    // ---------- sitemap.xml ----------

    public function test_the_sitemap_lists_only_pages_that_should_be_found(): void
    {
        $this->makePost(['slug' => 'live-post']);
        $this->makePost(['slug' => 'draft-post', 'status' => 'draft']);
        $this->makePost(['slug' => 'future-post', 'published_at' => now()->addWeek()]);
        $this->makePost(['slug' => 'hidden-from-search', 'noindex' => true]);

        $this->makeProduct(['slug' => 'visible-product']);
        $this->makeProduct(['slug' => 'draft-product', 'status' => 'draft']);
        $hidden = Category::create(['name' => 'Secret', 'slug' => 'secret', 'is_active' => false, 'sort_order' => 9]);
        $this->makeProduct(['slug' => 'secret-product', 'category_id' => $hidden->id]);

        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringContainsString('xml', $response->headers->get('Content-Type'));
        $this->assertNotFalse(simplexml_load_string($response->getContent()), 'The sitemap must be valid XML');

        $response->assertSee('/blog/live-post', false)
            ->assertSee('/products/visible-product', false)
            ->assertDontSee('draft-post')
            ->assertDontSee('future-post')
            ->assertDontSee('hidden-from-search')
            ->assertDontSee('draft-product')
            ->assertDontSee('secret-product');
    }

    // ---------- RSS feed ----------

    public function test_the_feed_is_valid_xml_with_live_posts_only(): void
    {
        $this->makePost(['title' => 'Fish & Chips <Décor>', 'slug' => 'fish']);
        $this->makePost(['title' => 'Draft Story', 'status' => 'draft']);
        $this->makePost(['title' => 'Private Story', 'noindex' => true]);

        $response = $this->get('/blog/feed.xml')->assertOk();

        $this->assertStringContainsString('xml', $response->headers->get('Content-Type'));
        $this->assertNotFalse(simplexml_load_string($response->getContent()), 'The feed must be valid XML');

        $response->assertSee('Fish &amp; Chips &lt;Décor&gt;', false)
            ->assertDontSee('Draft Story')
            ->assertDontSee('Private Story');
    }

    public function test_every_page_points_to_the_feed(): void
    {
        $this->get('/')->assertSee('application/rss+xml', false);
    }

    // ---------- article page ----------

    public function test_an_article_has_canonical_sharing_tags_and_structured_data(): void
    {
        $this->makePost(['slug' => 'seo-post', 'title' => 'SEO Post']);

        $this->get('/blog/seo-post')->assertOk()
            ->assertSee('<link rel="canonical" href="' . route('blog.show', 'seo-post') . '">', false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('property="article:published_time"', false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertDontSee('name="robots"', false);
    }

    public function test_a_noindex_article_still_opens_but_asks_search_engines_to_skip_it(): void
    {
        $this->makePost(['slug' => 'quiet-post', 'noindex' => true]);

        $this->get('/blog/quiet-post')->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_the_article_text_has_one_h1_numbered_headings_and_a_contents_box(): void
    {
        $this->makePost([
            'slug'    => 'long-post',
            'content' => "# Main\n\ntext\n\n## One\n\ntext\n\n## One\n\ntext\n\n### Deep\n\ntext",
        ]);

        $this->get('/blog/long-post')->assertOk()
            ->assertDontSee('<h1>Main</h1>', false)          // "# heading" is demoted
            ->assertSee('<h2 id="main">Main</h2>', false)
            ->assertSee('id="one"', false)
            ->assertSee('id="one-2"', false)                 // the same heading twice gets a different id
            ->assertSee('<h3 id="deep">Deep</h3>', false)
            ->assertSee('In this article');
    }

    public function test_a_short_article_has_no_contents_box(): void
    {
        $this->makePost(['slug' => 'short-post', 'content' => "## Only one\n\ntext"]);

        $this->get('/blog/short-post')->assertOk()->assertDontSee('In this article');
    }

    public function test_pictures_inside_an_article_load_lazily(): void
    {
        $this->makePost(['slug' => 'pic-post', 'content' => '![A lamp](https://example.com/lamp.jpg)']);

        $this->get('/blog/pic-post')->assertOk()
            ->assertSee('loading="lazy" decoding="async" src="https://example.com/lamp.jpg"', false);
    }

    // ---------- blog lists and homepage ----------

    public function test_page_two_has_its_own_canonical_and_search_points_to_the_plain_blog(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->makePost(['title' => "Post {$i}", 'published_at' => now()->subDays($i)]);
        }

        $this->get('/blog?page=2')->assertOk()
            ->assertSee('<link rel="canonical" href="' . route('blog.index') . '?page=2">', false);

        $this->get('/blog?q=Post')->assertOk()
            ->assertSee('<link rel="canonical" href="' . route('blog.index') . '">', false)
            ->assertSee('noindex', false);
    }

    public function test_the_blog_list_has_breadcrumb_data_and_a_category_has_its_own_canonical(): void
    {
        $category = BlogCategory::create(['name' => 'Bedroom', 'slug' => 'bedroom', 'sort_order' => 1]);
        $this->makePost(['blog_category_id' => $category->id]);

        $this->get('/blog/category/bedroom')->assertOk()
            ->assertSee('<link rel="canonical" href="' . route('blog.category', 'bedroom') . '">', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_the_homepage_says_who_you_are_and_that_it_has_a_search_box(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('"@type":"SearchAction"', false)
            ->assertSee('property="og:type" content="website"', false);
    }

    public function test_a_title_cannot_break_out_of_the_structured_data_or_the_sharing_tags(): void
    {
        $this->makePost(['slug' => 'evil', 'title' => '"><script>alert(1)</script>']);

        $this->get('/blog/evil')->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
