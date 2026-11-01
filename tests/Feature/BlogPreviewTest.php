<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogPreviewTest extends TestCase
{
    use RefreshDatabase;

    // (The helper names must not clash with Laravel's own get(), post()... test methods.)
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

    private function makeDraft(array $o = []): BlogPost
    {
        return $this->makeArticle(array_merge(['status' => 'draft', 'published_at' => null], $o));
    }

    // A valid private link, made the same way the site makes it
    private function signedLink(BlogPost $post): string
    {
        return URL::temporarySignedRoute('blog.preview', now()->addHour(), ['post' => $post->id]);
    }

    public function test_staff_get_a_private_link_that_shows_a_draft_without_logging_in(): void
    {
        $draft = $this->makeDraft(['title' => 'Secret Draft Title', 'slug' => 'secret-draft', 'content' => 'Draft words here.']);

        $answer = $this->actingAs($this->makeStaff())->get(route('admin.posts.preview-link', $draft));
        $answer->assertRedirect();
        $link = $answer->headers->get('Location');
        $this->assertStringContainsString('signature=', $link);

        auth()->logout();   // the link must work without a login

        $this->get($link)->assertOk()
            ->assertSee('Secret Draft Title')
            ->assertSee('Draft words here.')
            ->assertSee('not published yet')
            ->assertSee('noindex', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        // The draft itself is still hidden from the public
        $this->get('/blog/secret-draft')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('secret-draft');
    }

    public function test_a_live_article_can_be_previewed_too_and_says_so(): void
    {
        $live = $this->makeArticle(['title' => 'Live One', 'slug' => 'live-one']);

        $this->get($this->signedLink($live))->assertOk()
            ->assertSee('Live One')
            ->assertSee('Preview')
            ->assertDontSee('not published yet');
    }

    public function test_a_link_without_a_signature_is_refused(): void
    {
        $draft = $this->makeDraft();

        $this->get('/preview/blog/' . $draft->id)->assertForbidden();
    }

    public function test_changing_the_number_in_a_link_is_refused(): void
    {
        $mine  = $this->makeDraft(['title' => 'My Draft']);
        $other = $this->makeDraft(['title' => 'Other Private Draft']);

        $tampered = preg_replace('#/preview/blog/\d+#', '/preview/blog/' . $other->id, $this->signedLink($mine));

        $this->get($tampered)->assertForbidden();
    }

    public function test_an_expired_link_is_refused(): void
    {
        $draft = $this->makeDraft();

        $expired = URL::temporarySignedRoute('blog.preview', now()->subMinute(), ['post' => $draft->id]);

        $this->get($expired)->assertForbidden();
    }

    public function test_only_staff_can_create_a_link(): void
    {
        $draft = $this->makeDraft();

        $this->actingAs($this->makeCustomer())->get(route('admin.posts.preview-link', $draft))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.posts.preview-link', $draft))->assertRedirect(route('login'));
    }

    public function test_the_editor_offers_the_preview_button_for_saved_posts_only(): void
    {
        $post  = $this->makeDraft();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.posts.edit', $post))->assertOk()
            ->assertSee('Preview as visitors see it')
            ->assertSee(route('admin.posts.preview-link', $post), false);

        $this->actingAs($admin)->get(route('admin.posts.create'))->assertOk()
            ->assertDontSee('Preview as visitors see it');
    }

    public function test_a_title_in_a_preview_cannot_run_as_code(): void
    {
        $draft = $this->makeDraft(['title' => '<script>alert(1)</script>', 'slug' => 'evil']);

        $this->get($this->signedLink($draft))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_the_live_robots_file_keeps_search_engines_out_of_previews(): void
    {
        $this->app['env'] = 'production';

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /preview');
    }
}