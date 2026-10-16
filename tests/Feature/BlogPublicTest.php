<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogPublicTest extends TestCase
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

    private function category(string $name = 'Bedroom', string $slug = 'bedroom'): BlogCategory
    {
        return BlogCategory::firstOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => 1]);
    }

    // ---------- who can read what ----------

    public function test_the_blog_shows_only_live_posts(): void
    {
        $this->makePost(['title' => 'Live Article']);
        $this->makePost(['title' => 'Draft Article', 'status' => 'draft']);
        $this->makePost(['title' => 'Future Article', 'published_at' => now()->addWeek()]);

        $this->get('/blog')->assertOk()
            ->assertSee('Live Article')
            ->assertDontSee('Draft Article')
            ->assertDontSee('Future Article');
    }

    public function test_only_a_live_article_page_opens(): void
    {
        $this->makePost(['slug' => 'live-one', 'title' => 'Live One']);
        $this->makePost(['slug' => 'draft-one', 'status' => 'draft']);
        $this->makePost(['slug' => 'future-one', 'published_at' => now()->addWeek()]);

        $this->get('/blog/live-one')->assertOk()->assertSee('Live One');
        $this->get('/blog/draft-one')->assertNotFound();
        $this->get('/blog/future-one')->assertNotFound();
        $this->get('/blog/does-not-exist')->assertNotFound();
    }

    // ---------- safety ----------

    public function test_the_article_text_is_rendered_but_scripts_are_removed(): void
    {
        $this->makePost([
            'slug'    => 'safe-post',
            'content' => "Hello <script>alert(1)</script> **bold** [click](javascript:alert(2))\n\n<img src=x onerror=alert(3)>",
        ]);

        $this->get('/blog/safe-post')->assertOk()
            ->assertSee('<strong>bold</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('javascript:alert', false)
            ->assertDontSee('onerror', false);
    }

    public function test_a_title_cannot_break_out_of_the_structured_data(): void
    {
        $this->makePost(['slug' => 'evil', 'title' => '</script><script>alert(1)</script>']);

        $this->get('/blog/evil')->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertDontSee('</script><script>alert(1)</script>', false);
    }

    // ---------- category, tag, search ----------

    public function test_a_category_page_shows_only_its_own_posts(): void
    {
        $bedroom = $this->category();
        $kitchen = $this->category('Kitchen', 'kitchen');
        $this->makePost(['title' => 'Bedroom Piece', 'blog_category_id' => $bedroom->id]);
        $this->makePost(['title' => 'Kitchen Piece', 'blog_category_id' => $kitchen->id]);

        $this->get('/blog/category/bedroom')->assertOk()
            ->assertSee('Bedroom Piece')->assertDontSee('Kitchen Piece');
        $this->get('/blog/category/nothing')->assertNotFound();
    }

    public function test_a_tag_page_shows_only_tagged_posts(): void
    {
        $tag = Tag::create(['name' => 'Cozy', 'slug' => 'cozy']);
        $this->makePost(['title' => 'Tagged Post'])->tags()->attach($tag->id);
        $this->makePost(['title' => 'Plain Post']);

        $this->get('/blog/tag/cozy')->assertOk()
            ->assertSee('Tagged Post')->assertDontSee('Plain Post');
        $this->get('/blog/tag/nope')->assertNotFound();
    }

    public function test_search_looks_in_titles_and_in_the_article_text(): void
    {
        $this->makePost(['title' => 'Zebra Rug']);
        $this->makePost(['title' => 'Pattern Story', 'content' => 'The giraffe pattern is lovely.']);
        $this->makePost(['title' => 'Other One']);

        $this->get('/blog?q=Zebra')->assertOk()
            ->assertSee('Zebra Rug')->assertDontSee('Pattern Story')->assertDontSee('Other One');

        $this->get('/blog?q=giraffe')->assertOk()
            ->assertSee('Pattern Story')->assertDontSee('Zebra Rug');
    }

    public function test_odd_search_input_never_breaks_the_page(): void
    {
        $this->makePost();

        $this->get('/blog?q[]=x')->assertOk();
        $this->get('/blog?q=' . urlencode(str_repeat('a', 500)))->assertOk();
        $this->get('/blog?q=' . urlencode('%_\\'))->assertOk();
        $this->get('/blog?q=' . urlencode('<script>alert(1)</script>'))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    // ---------- the page features ----------

    public function test_the_newest_post_is_featured_on_the_blog_home_only(): void
    {
        $this->makePost(['title' => 'Older Post', 'published_at' => now()->subDays(5)]);
        $this->makePost(['title' => 'Newest Post', 'published_at' => now()->subDay()]);

        $this->get('/blog')->assertOk()
            ->assertSee('sgh-featured-post', false)
            ->assertSee('Newest Post')->assertSee('Older Post');

        $this->get('/blog?q=Older')->assertDontSee('sgh-featured-post', false);
    }

    public function test_an_article_shows_related_posts_and_shop_products(): void
    {
        $category = $this->category();
        $this->makePost(['slug' => 'main-post', 'blog_category_id' => $category->id]);
        $this->makePost(['title' => 'Sibling Article', 'blog_category_id' => $category->id]);
        $this->makeProduct(['name' => 'Gold Mirror', 'is_featured' => true]);

        $this->get('/blog/main-post')->assertOk()
            ->assertSee('Sibling Article')->assertSee('Gold Mirror');
    }

    public function test_the_homepage_shows_the_latest_posts(): void
    {
        $this->makePost(['title' => 'Fresh From The Blog']);
        $this->makePost(['title' => 'Hidden Draft Story', 'status' => 'draft']);

        $this->get('/')->assertOk()
            ->assertSee('Fresh From The Blog')->assertDontSee('Hidden Draft Story');
    }

    public function test_a_card_without_a_summary_uses_the_start_of_the_article(): void
    {
        $this->makePost(['title' => 'No Summary', 'excerpt' => null, 'content' => 'Cozy **corners** make homes warm.']);

        $this->get('/blog')->assertSee('Cozy corners make homes warm.');
    }

    public function test_search_results_and_empty_pages_are_kept_out_of_search_engines(): void
    {
        $this->get('/blog')->assertOk()->assertSee('noindex', false);   // no posts yet

        $this->makePost(['title' => 'Real Post']);

        $this->get('/blog')->assertDontSee('noindex', false);
        $this->get('/blog?q=zzzzzz')->assertSee('noindex', false);
    }
}
