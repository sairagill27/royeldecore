<?php

namespace Tests\Feature;

use App\Mail\AdminAlertMail;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

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

    private function makeWaitingComment(BlogPost $post, string $body = 'A waiting remark here'): Comment
    {
        $comment = new Comment(['name' => 'Sam', 'email' => 'sam@example.com', 'body' => $body]);
        $comment->blog_post_id = $post->id;
        $comment->status       = 'pending';
        $comment->save();

        return $comment;
    }

    // The same helpers as the review tests: a delivered order for a customer
    private function row(string $table, array $values): array
    {
        $row = [];

        foreach (Schema::getColumns($table) as $col) {
            $name = $col['name'];

            if (array_key_exists($name, $values)) {
                $row[$name] = $values[$name];
                continue;
            }
            if (in_array($name, ['created_at', 'updated_at'], true)) {
                $row[$name] = now();
                continue;
            }
            if (! empty($col['auto_increment']) || ! empty($col['nullable']) || $col['default'] !== null) {
                continue;
            }

            $type = strtolower((string) ($col['type_name'] ?? $col['type'] ?? ''));
            $row[$name] = match (true) {
                (bool) preg_match('/int|decimal|numeric|float|double|real|bool/', $type) => 1,
                (bool) preg_match('/datetime|timestamp/', $type)                          => now(),
                (bool) preg_match('/^date$/', $type)                                      => now()->toDateString(),
                (bool) preg_match('/json/', $type)                                        => '{}',
                default                                                                   => 'x',
            };
        }

        return $row;
    }

    private function deliveredOrderFor($user, Product $product): void
    {
        $orderId = DB::table('orders')->insertGetId($this->row('orders', [
            'user_id' => $user->id, 'status' => ProductReview::DELIVERED, 'order_number' => 'N-' . uniqid(),
        ]));
        DB::table('order_items')->insert($this->row('order_items', ['order_id' => $orderId, 'product_id' => $product->id]));
    }

    // ---------- the notifications page and the bell ----------

    public function test_staff_can_open_the_page_but_customers_and_visitors_cannot(): void
    {
        $this->actingAs($this->makeStaff())->get(route('admin.notifications.index'))->assertOk()->assertSee('Notifications');
        $this->actingAs($this->makeCustomer())->get(route('admin.notifications.index'))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.notifications.index'))->assertRedirect(route('login'));
    }

    public function test_an_empty_shop_says_you_are_all_caught_up(): void
    {
        $this->actingAs($this->makeAdmin())->get(route('admin.notifications.index'))->assertOk()
            ->assertSee('all caught up');
    }

    public function test_the_page_lists_what_is_waiting_and_low_stock(): void
    {
        config(['shop.low_stock' => 5]);
        $this->makeWaitingComment($this->makeArticle(), 'Please answer my question');
        $this->makeProduct(['name' => 'Almost Gone Vase', 'stock' => 2]);
        $this->makeProduct(['name' => 'Plenty Lamp', 'stock' => 50]);

        $this->actingAs($this->makeAdmin())->get(route('admin.notifications.index'))->assertOk()
            ->assertSee('Please answer my question')
            ->assertSee('Almost Gone Vase')
            ->assertDontSee('Plenty Lamp')
            ->assertDontSee('all caught up');
    }

    public function test_a_waiting_comment_shows_on_the_bell(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('sgh-bell', false)
            ->assertDontSee('sgh-bell-count', false);

        $this->makeWaitingComment($this->makeArticle());

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('sgh-bell-count', false);
    }

    public function test_what_visitors_wrote_is_never_run_as_code_on_the_page(): void
    {
        $this->makeWaitingComment($this->makeArticle(), '<script>alert(1)</script> hello there');

        $this->actingAs($this->makeAdmin())->get(route('admin.notifications.index'))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    // ---------- email alerts ----------

    public function test_a_comment_waiting_sends_the_owner_an_email(): void
    {
        Mail::fake();
        config(['shop.admin_email' => 'owner@example.com']);
        $post = $this->makeArticle(['slug' => 'live']);

        $this->post(route('blog.comments.store', 'live'), ['name' => 'Sam', 'email' => 'sam@example.com', 'body' => 'Lovely ideas, thank you!'])
            ->assertSessionHas('success');

        Mail::assertSent(AdminAlertMail::class, fn (AdminAlertMail $mail) => $mail->hasTo('owner@example.com')
            && str_contains($mail->subjectLine, 'comment')
            && str_contains($mail->url, 'comments'));
    }

    public function test_a_review_waiting_sends_the_owner_an_email(): void
    {
        Mail::fake();
        config(['shop.admin_email' => 'owner@example.com']);
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();
        $this->deliveredOrderFor($customer, $product);

        $this->actingAs($customer)->post(route('products.reviews.store', $product->slug), [
            'rating' => 5, 'title' => 'Great', 'body' => 'Beautiful quality, I love it.',
        ])->assertSessionHas('success');

        Mail::assertSent(AdminAlertMail::class, fn (AdminAlertMail $mail) => $mail->hasTo('owner@example.com')
            && str_contains($mail->subjectLine, 'review'));
    }

    public function test_no_email_is_sent_without_a_valid_owner_address(): void
    {
        Mail::fake();
        $post = $this->makeArticle(['slug' => 'live']);

        foreach ([null, '', 'not-an-email'] as $address) {
            config(['shop.admin_email' => $address]);
            $this->post(route('blog.comments.store', 'live'), [
                'name' => 'Sam', 'email' => 'sam' . uniqid() . '@example.com', 'body' => 'Another remark number ' . uniqid(),
            ]);
        }

        Mail::assertNothingSent();
    }

    public function test_a_staff_comment_that_is_published_at_once_sends_no_alert(): void
    {
        Mail::fake();
        config(['shop.admin_email' => 'owner@example.com']);
        $this->makeArticle(['slug' => 'live']);

        $this->actingAs($this->makeStaff())->post(route('blog.comments.store', 'live'), ['body' => 'From the team!']);

        Mail::assertNothingSent();
    }

    public function test_a_broken_mail_server_never_stops_the_comment(): void
    {
        config(['shop.admin_email' => 'owner@example.com']);
        $this->makeArticle(['slug' => 'live']);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('mail server is down'));

        $this->post(route('blog.comments.store', 'live'), ['name' => 'Sam', 'email' => 'sam@example.com', 'body' => 'Lovely ideas, thank you!'])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('comments', 1);
    }

    public function test_the_alert_email_escapes_everything_it_shows(): void
    {
        $html = (new AdminAlertMail('Subject', '<b>Head</b>', ['<script>alert(1)</script>'], 'https://example.com/admin', 'Open'))->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<b>Head</b>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('https://example.com/admin', $html);
    }

    // ---------- public contact details ----------

    public function test_contact_details_appear_in_the_footer_only_when_filled(): void
    {
        config(['contact.email' => null, 'contact.phone' => null, 'contact.whatsapp' => null, 'contact.address' => null, 'contact.hours' => null]);
        $this->get('/')->assertOk()->assertDontSee('sgh-contact-item', false)->assertDontSee('wa.me', false);

        config([
            'contact.email'    => 'hello@example.com',
            'contact.phone'    => '+92 300 1234567',
            'contact.whatsapp' => '+92 300-1234567',
            'contact.address'  => '12 Main Road, Islamabad',
            'contact.hours'    => 'Mon-Sat, 10am to 7pm',
        ]);

        $this->get('/')->assertOk()
            ->assertSee('mailto:hello@example.com', false)
            ->assertSee('tel:+923001234567', false)
            ->assertSee('https://wa.me/923001234567', false)
            ->assertSee('12 Main Road, Islamabad')
            ->assertSee('Mon-Sat, 10am to 7pm');
    }

    public function test_a_bad_email_or_number_is_not_shown_and_text_is_escaped(): void
    {
        config([
            'contact.email'    => 'not-an-email',
            'contact.phone'    => 'call me',
            'contact.whatsapp' => '123',
            'contact.address'  => '<script>alert(1)</script>',
            'contact.hours'    => '',
        ]);

        $this->get('/')->assertOk()
            ->assertDontSee('mailto:', false)
            ->assertDontSee('tel:', false)
            ->assertDontSee('wa.me', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}