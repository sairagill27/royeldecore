<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SlugRedirectTest extends TestCase
{
    use RefreshDatabase;

    // Makes a live article. Pass values to change any of them.
    private function makePost(array $overrides = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'user_id'      => $this->makeAdmin()->id,
            'title'        => 'A test post',
            'slug'         => 'post-' . Str::lower(Str::random(8)),
            'content'      => 'Some content.',
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    // Saves the post through the admin form, with a new slug (and optionally a new status)
    private function rename(BlogPost $post, string $slug, string $status = 'published')
    {
        return $this->actingAs($this->makeAdmin())->put(route('admin.posts.update', $post), [
            'title'   => $post->title,
            'content' => 'Some content.',
            'status'  => $status,
            'slug'    => $slug,
        ]);
    }

    public function test_renaming_a_live_article_redirects_the_old_address(): void
    {
        $post = $this->makePost(['slug' => 'old-name']);

        $this->rename($post, 'new-name')->assertSessionHasNoErrors();

        $this->get('/blog/new-name')->assertOk();
        $this->get('/blog/old-name')->assertStatus(301)->assertRedirect(route('blog.show', 'new-name'));
    }

    public function test_renaming_a_draft_makes_no_redirect(): void
    {
        $post = $this->makePost(['slug' => 'draft-old', 'status' => 'draft', 'published_at' => null]);

        $this->rename($post, 'draft-new', 'draft');

        $this->assertDatabaseCount('slug_redirects', 0);
        $this->get('/blog/draft-old')->assertNotFound();
    }

    public function test_a_second_rename_sends_every_old_address_to_the_newest(): void
    {
        $post = $this->makePost(['slug' => 'a-name']);

        $this->rename($post, 'b-name');
        $this->rename($post->fresh(), 'c-name');

        $this->get('/blog/a-name')->assertStatus(301)->assertRedirect(route('blog.show', 'c-name'));
        $this->get('/blog/b-name')->assertStatus(301)->assertRedirect(route('blog.show', 'c-name'));
    }

    public function test_a_new_article_can_take_over_an_old_address(): void
    {
        $post  = $this->makePost(['slug' => 'old-name']);
        $admin = $this->makeAdmin();

        $this->rename($post, 'new-name');

        $this->actingAs($admin)->post(route('admin.posts.store'), [
            'title' => 'Fresh Post',
            'content' => 'Fresh words here.',
            'status' => 'published',
            'slug' => 'old-name',
        ])->assertSessionHasNoErrors();

        $this->get('/blog/old-name')->assertOk()->assertSee('Fresh Post');
        $this->assertDatabaseMissing('slug_redirects', ['old_slug' => 'old-name']);
    }

    public function test_renaming_back_removes_the_redirect_that_pointed_away(): void
    {
        $post = $this->makePost(['slug' => 'old-name']);

        $this->rename($post, 'new-name');
        $this->rename($post->fresh(), 'old-name');

        $this->assertDatabaseMissing('slug_redirects', ['old_slug' => 'old-name']);
        $this->get('/blog/new-name')->assertStatus(301)->assertRedirect(route('blog.show', 'old-name'));
    }

    public function test_a_redirect_to_an_article_that_is_no_longer_live_shows_404(): void
    {
        $post = $this->makePost(['slug' => 'old-name']);

        $this->rename($post, 'new-name');
        $this->rename($post->fresh(), 'new-name', 'draft');   // taken offline

        $this->get('/blog/old-name')->assertNotFound();
    }

    public function test_saving_without_changing_the_address_makes_no_redirect(): void
    {
        $post = $this->makePost(['slug' => 'same-slug']);

        $this->rename($post, 'same-slug');

        $this->assertDatabaseCount('slug_redirects', 0);
    }
}
