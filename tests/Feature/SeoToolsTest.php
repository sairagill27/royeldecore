<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Services\SeoScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoToolsTest extends TestCase
{
    use RefreshDatabase;

    // A post that passes all eleven checks
    private function goodData(array $overrides = []): array
    {
        return array_merge([
            'title'            => 'Small Bedroom Ideas That Make Space Feel Bigger',
            'seo_title'        => null,
            'meta_description' => 'Small bedroom tips. ' . str_repeat('a', 120),
            'focus_keyword'    => 'small bedroom',
            'slug'             => 'small-bedroom-ideas',
            'content'          => "Small bedroom design starts with light. See [our shop](/shop).\n\n## One\n\n" . str_repeat('word ', 620) . "\n\n## Two\n\nMore.",
            'has_image'        => true,
            'image_alt'        => 'A small bedroom with a grey bed',
        ], $overrides);
    }

    // Saves a post straight in the database (live, unless you change it)
    private function savePost(array $overrides = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'user_id'      => $this->makeAdmin()->id,
            'title'        => 'A test post',
            'slug'         => 'test-' . uniqid(),
            'content'      => 'Some content.',
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    // Form values the editor sends
    private function formData(array $overrides = []): array
    {
        return array_merge([
            'title'   => 'Cozy Bedroom Ideas',
            'content' => 'Some **bold** words.',
            'status'  => 'draft',
        ], $overrides);
    }

    // ---------- the scoring rules ----------

    public function test_a_well_written_post_scores_100(): void
    {
        $result = SeoScore::analyze($this->goodData());

        $this->assertSame(100, $result['score']);
        $this->assertSame('good', SeoScore::level($result['score']));
    }

    public function test_a_bare_post_scores_low_and_says_what_is_missing(): void
    {
        $result = SeoScore::analyze(['title' => 'A test post', 'content' => 'Some content.', 'slug' => 'a-test-post']);

        $this->assertLessThan(20, $result['score']);
        $this->assertSame('bad', SeoScore::level($result['score']));

        $failed = collect($result['checks'])->where('ok', false)->pluck('label');
        $this->assertTrue($failed->contains('Focus keyword is set'));
        $this->assertTrue($failed->contains('Article is at least 600 words'));
    }

    public function test_without_a_keyword_every_keyword_check_fails(): void
    {
        // Keyword checks: set 10 + title 10 + meta 10 + first 100 words 10 + web address 5 = 45 points
        $this->assertSame(55, SeoScore::analyze($this->goodData(['focus_keyword' => '']))['score']);
    }

    public function test_a_title_that_is_too_long_loses_points(): void
    {
        $result = SeoScore::analyze($this->goodData(['seo_title' => 'Small bedroom ' . str_repeat('x', 60)]));

        $this->assertSame(90, $result['score']);   // only the title-length check fails
    }

    public function test_the_levels_are_good_ok_and_bad(): void
    {
        $this->assertSame('good', SeoScore::level(80));
        $this->assertSame('ok', SeoScore::level(79));
        $this->assertSame('ok', SeoScore::level(50));
        $this->assertSame('bad', SeoScore::level(49));
    }

    public function test_internal_links_are_detected(): void
    {
        $check = fn(string $content): bool => collect(SeoScore::analyze($this->goodData(['content' => $content]))['checks'])
            ->firstWhere('label', 'Links to another page on your site')['ok'];

        $this->assertTrue($check('See [our shop](/shop) now'));
        $this->assertTrue($check('See [our shop](' . url('/shop') . ') now'));
        $this->assertFalse($check('See [a site](https://example.com) now'));
        $this->assertFalse($check('See [odd](//evil.example) now'));
        $this->assertFalse($check('No links here'));
    }

    // ---------- the editor ----------

    public function test_the_editor_shows_the_preview_the_score_and_the_new_fields(): void
    {
        $this->actingAs($this->makeAdmin())->get(route('admin.posts.create'))->assertOk()
            ->assertSee('Google preview')
            ->assertSee('SEO score')
            ->assertSee('name="focus_keyword"', false)
            ->assertSee('name="noindex"', false);
    }

    public function test_the_keyword_and_noindex_are_saved(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.posts.store'), $this->formData(['focus_keyword' => '  small   bedroom ', 'noindex' => '1']))
            ->assertSessionHasNoErrors();

        $post = BlogPost::firstOrFail();
        $this->assertSame('small bedroom', $post->focus_keyword);   // extra spaces are tidied
        $this->assertTrue($post->noindex);
    }

    public function test_an_unticked_noindex_is_saved_as_false(): void
    {
        $post = $this->savePost(['slug' => 'quiet', 'noindex' => true]);

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.posts.update', $post), $this->formData(['slug' => 'quiet', 'noindex' => '0']))
            ->assertSessionHasNoErrors();

        $this->assertFalse($post->fresh()->noindex);
    }

    public function test_an_overlong_keyword_is_refused(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.posts.store'), $this->formData(['focus_keyword' => str_repeat('a', 81)]))
            ->assertSessionHasErrors('focus_keyword');

        $this->assertDatabaseCount('blog_posts', 0);
    }

    public function test_hiding_a_post_removes_it_from_the_sitemap_straight_away(): void
    {
        $post = $this->savePost(['slug' => 'sm-post']);

        $this->get('/sitemap.xml')->assertSee('/blog/sm-post', false);   // listed (and cached)

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.posts.update', $post), $this->formData([
                'slug' => 'sm-post',
                'status' => 'published',
                'noindex' => '1',
            ]));

        $this->get('/sitemap.xml')->assertDontSee('sm-post');            // gone, no waiting
    }

    // ---------- the SEO audit page ----------

    public function test_the_audit_lists_the_weakest_post_first(): void
    {
        $this->savePost(['title' => 'Zz Bad Post', 'slug' => 'bad']);
        $this->savePost([
            'title'              => 'Small Bedroom Ideas That Make Space Feel Bigger',
            'slug'               => 'small-bedroom-ideas',
            'meta_description'   => 'Small bedroom tips. ' . str_repeat('a', 120),
            'focus_keyword'      => 'small bedroom',
            'content'            => "Small bedroom design starts with light. See [our shop](/shop).\n\n## One\n\n" . str_repeat('word ', 620) . "\n\n## Two\n\nMore.",
            'featured_image'     => 'blog/x.jpg',
            'featured_image_alt' => 'A small bedroom',
        ]);

        $this->actingAs($this->makeAdmin())->get(route('admin.posts.audit'))->assertOk()
            ->assertSeeInOrder(['Zz Bad Post', 'Small Bedroom Ideas That Make Space Feel Bigger'])
            ->assertSee('Focus keyword is set');
    }

    public function test_the_audit_works_with_no_posts(): void
    {
        $this->actingAs($this->makeStaff())->get(route('admin.posts.audit'))->assertOk()->assertSee('No posts yet');
    }

    public function test_the_audit_is_for_staff_only(): void
    {
        $this->actingAs($this->makeCustomer())->get(route('admin.posts.audit'))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.posts.audit'))->assertRedirect(route('login'));
    }
}
