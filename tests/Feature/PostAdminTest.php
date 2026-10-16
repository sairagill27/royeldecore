<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostAdminTest extends TestCase
{
    use RefreshDatabase;

    private function postData(array $overrides = []): array
    {
        $category = BlogCategory::firstOrCreate(['slug' => 'bedroom'], ['name' => 'Bedroom', 'sort_order' => 1]);

        return array_merge([
            'title'            => 'Cozy Bedroom Ideas',
            'content'          => 'Some **bold** words.',
            'blog_category_id' => $category->id,
            'status'           => 'draft',
        ], $overrides);
    }

    // Makes a post straight in the database
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

    // ---------- who can open it ----------

    public function test_staff_and_admin_can_open_the_post_pages_but_customers_cannot(): void
    {
        $post = $this->makePost();

        foreach ([$this->makeAdmin(), $this->makeStaff()] as $user) {
            $this->actingAs($user)->get(route('admin.posts.index'))->assertOk();
            $this->actingAs($user)->get(route('admin.posts.create'))->assertOk();
            $this->actingAs($user)->get(route('admin.posts.edit', $post))->assertOk();
        }

        $customer = $this->makeCustomer();
        $this->actingAs($customer)->get(route('admin.posts.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.posts.create'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.posts.store'), $this->postData())->assertForbidden();

        $this->assertDatabaseCount('blog_posts', 1);   // only the one made at the start
    }

    public function test_visitors_are_sent_to_the_login_page(): void
    {
        $this->get(route('admin.posts.index'))->assertRedirect(route('login'));
    }

    // ---------- writing ----------

    public function test_a_draft_is_saved_with_a_slug_and_the_author_cannot_be_forged(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeCustomer();

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), $this->postData(['user_id' => $other->id]))
            ->assertSessionHasNoErrors();

        $post = BlogPost::firstOrFail();
        $this->assertSame('cozy-bedroom-ideas', $post->slug);
        $this->assertSame('draft', $post->status);
        $this->assertNull($post->published_at);
        $this->assertSame($admin->id, $post->user_id);   // not the forged id
    }

    public function test_publishing_without_a_date_publishes_now(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.posts.store'), $this->postData(['status' => 'published']));

        $this->assertSame(1, BlogPost::published()->count());
    }

    public function test_a_future_date_is_scheduled_and_saved_in_utc(): void
    {
        config(['shop.timezone' => 'Asia/Karachi']);   // 5 hours ahead of UTC

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.posts.store'), $this->postData([
                'status' => 'published',
                'published_at' => '2035-06-01T10:00',
            ]));

        $post = BlogPost::firstOrFail();
        $this->assertSame('2035-06-01 05:00', $post->published_at->utc()->format('Y-m-d H:i'));
        $this->assertSame(0, BlogPost::published()->count());   // not live yet
    }

    public function test_tags_are_created_and_duplicates_ignored(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.posts.store'), $this->postData(['tags' => 'Cozy, small space, cozy, ']));

        $this->assertDatabaseCount('tags', 2);
        $this->assertCount(2, BlogPost::firstOrFail()->tags);
    }

    public function test_too_many_or_too_long_tags_are_refused(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), $this->postData(['tags' => implode(',', range(1, 11))]))
            ->assertSessionHasErrors('tags');

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), $this->postData(['tags' => str_repeat('a', 41)]))
            ->assertSessionHasErrors('tags');

        $this->assertDatabaseCount('blog_posts', 0);
    }

    public function test_bad_post_details_are_refused(): void
    {
        $admin = $this->makeAdmin();
        $this->makePost(['slug' => 'taken-slug']);

        $cases = [
            ['title' => null],                            // no title
            ['content' => null],                          // no text
            ['blog_category_id' => 9999],                 // category that does not exist
            ['slug' => 'Bad Slug!'],                      // not a clean web address
            ['slug' => 'taken-slug'],                     // already used
            ['status' => 'hacked'],                       // not a real status
            ['published_at' => 'tomorrow'],               // not a valid date
            ['content' => str_repeat('a', 60001)],        // too long
        ];

        foreach ($cases as $bad) {
            $this->actingAs($admin)
                ->post(route('admin.posts.store'), $this->postData($bad))
                ->assertSessionHasErrors(array_key_first($bad));
        }

        $this->assertDatabaseCount('blog_posts', 1);   // only the one made at the start
    }

    // ---------- photos ----------

    public function test_a_file_that_is_not_an_image_is_refused(): void
    {
        Storage::fake('public');

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.posts.store'), $this->postData([
                'featured_image'     => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
                'featured_image_alt' => 'A photo',
            ]))
            ->assertSessionHasErrors('featured_image');

        $this->assertDatabaseCount('blog_posts', 0);
    }

    public function test_a_photo_needs_a_description_and_is_saved_under_a_random_name(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('The PHP "gd" extension is off.');
        }
        Storage::fake('public');
        $admin = $this->makeAdmin();

        // No description: refused
        $this->actingAs($admin)
            ->post(route('admin.posts.store'), $this->postData([
                'featured_image' => UploadedFile::fake()->image('my photo.jpg', 600, 400),
            ]))
            ->assertSessionHasErrors('featured_image_alt');

        // With a description: saved
        $this->actingAs($admin)
            ->post(route('admin.posts.store'), $this->postData([
                'featured_image'     => UploadedFile::fake()->image('my photo.jpg', 600, 400),
                'featured_image_alt' => 'A bright bedroom',
            ]));

        $post = BlogPost::firstOrFail();
        $this->assertStringStartsWith('blog/', $post->featured_image);
        $this->assertStringNotContainsString('my photo', $post->featured_image);
        Storage::disk('public')->assertExists($post->featured_image);
    }

    // ---------- editing, deleting, listing ----------

    public function test_editing_can_unpublish_a_post_and_keep_its_own_slug(): void
    {
        $post = $this->makePost(['slug' => 'my-post']);

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.posts.update', $post), $this->postData([
                'title' => 'Renamed',
                'slug' => 'my-post',
                'status' => 'draft',
            ]))
            ->assertRedirect(route('admin.posts.edit', $post));

        $this->assertSame('draft', $post->fresh()->status);
        $this->assertSame('Renamed', $post->fresh()->title);
        $this->assertSame(0, BlogPost::published()->count());
    }

    public function test_only_an_admin_can_delete_a_post(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->makeStaff())->delete(route('admin.posts.destroy', $post))->assertForbidden();
        $this->assertDatabaseHas('blog_posts', ['id' => $post->id]);

        $this->actingAs($this->makeAdmin())->delete(route('admin.posts.destroy', $post))
            ->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    }

    public function test_the_list_can_be_filtered_by_status(): void
    {
        $this->makePost(['title' => 'Live Article']);
        $this->makePost(['title' => 'Draft Article', 'status' => 'draft']);
        $this->makePost(['title' => 'Future Article', 'published_at' => now()->addWeek()]);

        $admin = $this->actingAs($this->makeAdmin());

        $admin->get(route('admin.posts.index', ['status' => 'draft']))
            ->assertSee('Draft Article')->assertDontSee('Live Article')->assertDontSee('Future Article');

        $admin->get(route('admin.posts.index', ['status' => 'scheduled']))
            ->assertSee('Future Article')->assertDontSee('Live Article')->assertDontSee('Draft Article');

        $admin->get(route('admin.posts.index', ['status' => 'published']))
            ->assertSee('Live Article')->assertDontSee('Draft Article')->assertDontSee('Future Article');
    }

    // ---------- the safe preview ----------

    public function test_the_preview_removes_scripts_and_dangerous_links(): void
    {
        $admin = $this->makeAdmin();

        // A script in the middle of a line, and a javascript: link
        $html = $this->actingAs($admin)
            ->postJson(route('admin.posts.preview'), [
                'content' => "Hello <script>alert('x')</script> **bold** [click](javascript:alert(1))",
            ])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);

        // A script at the start of a line (raw HTML block), a picture with an event handler, and a link
        $html = $this->actingAs($admin)
            ->postJson(route('admin.posts.preview'), [
                'content' => "<script>alert('x')</script>\n\n<img src=x onerror=alert(1)>\n\nSafe **text**",
            ])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('<strong>text</strong>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_customers_cannot_use_the_preview(): void
    {
        $this->actingAs($this->makeCustomer())
            ->postJson(route('admin.posts.preview'), ['content' => 'hello'])
            ->assertForbidden();
    }
}
