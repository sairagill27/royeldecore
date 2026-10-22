<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommentTest extends TestCase
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

    // Makes a comment straight in the database
    private function makeComment(BlogPost $post, array $o = []): Comment
    {
        $comment = new Comment([
            'name'  => $o['name'] ?? 'Sam',
            'email' => $o['email'] ?? 'sam@example.com',
            'body'  => $o['body'] ?? 'Nice article, thanks!',
        ]);
        $comment->blog_post_id = $post->id;
        $comment->status       = $o['status'] ?? 'approved';
        $comment->save();

        return $comment;
    }

    private function send(BlogPost $post, array $data = [])
    {
        return $this->post(route('blog.comments.store', $post->slug), array_merge([
            'name' => 'Sam',
            'email' => 'Sam@Example.com',
            'body' => 'Lovely ideas, thank you!',
        ], $data));
    }

    // ---------- posting ----------

    public function test_a_visitor_can_comment_and_it_waits_for_approval(): void
    {
        $post = $this->makePost(['slug' => 'live']);

        $this->send($post)->assertRedirect(route('blog.show', 'live') . '#comments')->assertSessionHas('success');

        $comment = Comment::firstOrFail();
        $this->assertSame('pending', $comment->status);
        $this->assertSame('sam@example.com', $comment->email);   // saved in lowercase

        $this->get('/blog/live')->assertOk()->assertDontSee('Lovely ideas, thank you!');
    }

    public function test_only_approved_comments_are_shown_and_emails_never_are(): void
    {
        $post = $this->makePost(['slug' => 'live']);
        $this->makeComment($post, ['body' => 'Approved words', 'email' => 'secret@example.com']);
        $this->makeComment($post, ['body' => 'Pending words', 'status' => 'pending']);
        $this->makeComment($post, ['body' => 'Rejected words', 'status' => 'rejected']);

        $this->get('/blog/live')->assertOk()
            ->assertSee('Approved words')
            ->assertSee('Comments (1)')
            ->assertDontSee('Pending words')
            ->assertDontSee('Rejected words')
            ->assertDontSee('secret@example.com');
    }

    public function test_html_in_a_comment_is_shown_as_plain_text(): void
    {
        $post = $this->makePost(['slug' => 'live']);
        $this->makeComment($post, ['name' => '<b>Evil</b>', 'body' => '<script>alert(1)</script> hello there']);

        $this->get('/blog/live')->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<b>Evil</b>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_a_logged_in_customer_uses_their_own_name_and_cannot_forge_it(): void
    {
        $post     = $this->makePost();
        $customer = $this->makeCustomer(['name' => 'Real Name', 'email' => 'real@example.com']);

        $this->actingAs($customer)->send($post, ['name' => 'Fake', 'email' => 'fake@example.com']);

        $comment = Comment::firstOrFail();
        $this->assertSame('Real Name', $comment->name);
        $this->assertSame('real@example.com', $comment->email);
        $this->assertSame($customer->id, $comment->user_id);
        $this->assertSame('pending', $comment->status);
    }

    public function test_a_staff_comment_is_published_at_once(): void
    {
        $post = $this->makePost(['slug' => 'live']);

        $this->actingAs($this->makeStaff(['name' => 'Editor']))->send($post, ['body' => 'From the team!']);

        $this->assertSame('approved', Comment::firstOrFail()->status);
        $this->get('/blog/live')->assertSee('From the team!');
    }

    public function test_a_visitor_cannot_choose_the_status(): void
    {
        $post = $this->makePost();

        $this->send($post, ['status' => 'approved', 'blog_post_id' => 999, 'user_id' => 1]);

        $comment = Comment::firstOrFail();
        $this->assertSame('pending', $comment->status);
        $this->assertSame($post->id, $comment->blog_post_id);
        $this->assertNull($comment->user_id);
    }

    // ---------- checks ----------

    public function test_missing_or_bad_details_are_refused(): void
    {
        $post = $this->makePost();

        foreach ([['name' => ''], ['email' => 'nope'], ['body' => ''], ['body' => 'hi'], ['body' => str_repeat('a', 1501), 'name' => str_repeat('a', 81)]] as $bad) {
            $this->send($post, $bad)->assertSessionHasErrors(array_keys($bad));
        }

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_odd_input_never_breaks_the_page(): void
    {
        $post = $this->makePost();

        $this->post(route('blog.comments.store', $post->slug), ['name' => ['x'], 'email' => ['y'], 'body' => ['z']])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_one_link_is_fine_but_a_pile_of_links_is_refused(): void
    {
        $post = $this->makePost();

        $this->send($post, ['body' => 'Lovely, see https://example.com for more'])->assertSessionHasNoErrors();
        $this->send($post, ['body' => 'Buy http://a.com and http://b.com and www.c.com now'])->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 1);
    }

    public function test_the_spam_trap_saves_nothing(): void
    {
        $post = $this->makePost();

        $this->send($post, ['website' => 'http://spam.example'])->assertSessionHas('success');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_the_same_comment_twice_is_saved_once(): void
    {
        $post = $this->makePost();

        $this->send($post)->assertSessionHas('success');
        $this->send($post)->assertSessionHas('success');

        $this->assertDatabaseCount('comments', 1);
    }

    public function test_a_person_with_five_comments_waiting_cannot_add_more(): void
    {
        $post = $this->makePost();
        for ($i = 1; $i <= 5; $i++) {
            $this->makeComment($post, ['email' => 'busy@example.com', 'body' => "Waiting comment {$i}", 'status' => 'pending']);
        }

        $this->send($post, ['email' => 'busy@example.com', 'body' => 'One more please'])->assertSessionHas('error');

        $this->assertDatabaseCount('comments', 5);
    }

    public function test_the_sixth_comment_in_ten_minutes_is_blocked(): void
    {
        $post = $this->makePost();

        for ($i = 1; $i <= 5; $i++) {
            $this->send($post, ['email' => "user{$i}@example.com", 'body' => "Comment number {$i}"])->assertRedirect();
        }

        $this->send($post, ['email' => 'user6@example.com', 'body' => 'Comment number 6'])->assertStatus(429);
    }

    public function test_drafts_and_scheduled_articles_cannot_be_commented_on(): void
    {
        $draft  = $this->makePost(['status' => 'draft']);
        $future = $this->makePost(['published_at' => now()->addWeek()]);

        $this->send($draft)->assertNotFound();
        $this->send($future)->assertNotFound();

        $this->assertDatabaseCount('comments', 0);
    }

    // ---------- the admin page ----------

    public function test_staff_can_approve_and_reject_but_only_an_admin_can_delete(): void
    {
        $post    = $this->makePost();
        $comment = $this->makeComment($post, ['status' => 'pending']);
        $staff   = $this->makeStaff();

        $this->actingAs($staff)->patch(route('admin.comments.approve', $comment))->assertSessionHas('success');
        $this->assertSame('approved', $comment->fresh()->status);

        $this->actingAs($staff)->patch(route('admin.comments.reject', $comment))->assertSessionHas('success');
        $this->assertSame('rejected', $comment->fresh()->status);

        $this->actingAs($staff)->delete(route('admin.comments.destroy', $comment))->assertForbidden();
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);

        $this->actingAs($this->makeAdmin())->delete(route('admin.comments.destroy', $comment))->assertSessionHas('success');
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_the_admin_list_filters_by_status_and_is_staff_only(): void
    {
        $post = $this->makePost();
        $this->makeComment($post, ['body' => 'Waiting remark', 'status' => 'pending']);
        $this->makeComment($post, ['body' => 'Public remark', 'status' => 'approved']);

        $this->actingAs($this->makeStaff())->get(route('admin.comments.index', ['status' => 'pending']))
            ->assertOk()->assertSee('Waiting remark')->assertDontSee('Public remark');

        $this->actingAs($this->makeCustomer())->get(route('admin.comments.index'))->assertForbidden();
        $this->actingAs($this->makeCustomer())
            ->patch(route('admin.comments.approve', Comment::first()))->assertForbidden();

        Auth::logout();
        $this->get(route('admin.comments.index'))->assertRedirect(route('login'));
    }

    public function test_deleting_an_article_deletes_its_comments(): void
    {
        $post = $this->makePost();
        $this->makeComment($post);

        $post->delete();

        $this->assertDatabaseCount('comments', 0);
    }
}
