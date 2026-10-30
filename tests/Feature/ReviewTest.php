<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    // Builds a row for a table: the values given, plus a harmless filler for every other required column
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

    // An order containing the product, with the given status
    private function orderFor($user, Product $product, string $status = ProductReview::DELIVERED): void
    {
        $orderId = DB::table('orders')->insertGetId($this->row('orders', [
            'user_id'      => $user->id,
            'status'       => $status,
            'order_number' => 'T-' . uniqid(),
        ]));

        DB::table('order_items')->insert($this->row('order_items', [
            'order_id'   => $orderId,
            'product_id' => $product->id,
        ]));
    }

    // A review straight in the database
    private function makeReview(Product $product, $user, array $o = []): ProductReview
    {
        $review = new ProductReview([
            'rating' => $o['rating'] ?? 5,
            'title'  => $o['title'] ?? null,
            'body'   => $o['body'] ?? 'Lovely piece, exactly as described.',
        ]);
        $review->product_id = $product->id;
        $review->user_id    = $user->id;
        $review->status     = $o['status'] ?? 'approved';
        $review->save();

        return $review;
    }

    private function send($user, Product $product, array $data = [])
    {
        return $this->actingAs($user)->post(route('products.reviews.store', $product->slug), array_merge([
            'rating' => 5, 'title' => 'Great', 'body' => 'Beautiful quality, I love it.',
        ], $data));
    }

    // ---------- who can review ----------

    public function test_visitors_are_sent_to_the_login_page(): void
    {
        $product = $this->makeProduct();

        $this->post(route('products.reviews.store', $product->slug), ['rating' => 5, 'body' => 'A fine product indeed.'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_a_customer_who_never_received_the_product_cannot_review_it(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();

        $this->send($customer, $product)->assertForbidden();
        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_an_order_that_is_not_delivered_yet_does_not_count(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();
        $this->orderFor($customer, $product, 'processing');

        $this->send($customer, $product)->assertForbidden();
        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_someone_elses_order_does_not_count(): void
    {
        $buyer    = $this->makeCustomer();
        $stranger = $this->makeCustomer();
        $product  = $this->makeProduct();
        $this->orderFor($buyer, $product);

        $this->send($stranger, $product)->assertForbidden();
        $this->assertDatabaseCount('product_reviews', 0);
    }

    // ---------- writing a review ----------

    public function test_a_buyer_can_review_and_it_waits_for_approval(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();
        $this->orderFor($customer, $product);

        $this->send($customer, $product)->assertRedirect(route('products.show', $product->slug) . '#reviews')
            ->assertSessionHas('success');

        $review = ProductReview::firstOrFail();
        $this->assertSame('pending', $review->status);
        $this->assertSame(5, $review->rating);

        $this->get(route('products.show', $product->slug))->assertOk()->assertDontSee('Beautiful quality, I love it.');
    }

    public function test_a_customer_cannot_choose_the_status_or_the_author(): void
    {
        $customer = $this->makeCustomer();
        $other    = $this->makeCustomer();
        $product  = $this->makeProduct();
        $this->orderFor($customer, $product);

        $this->send($customer, $product, ['status' => 'approved', 'user_id' => $other->id, 'product_id' => 999]);

        $review = ProductReview::firstOrFail();
        $this->assertSame('pending', $review->status);
        $this->assertSame($customer->id, $review->user_id);
        $this->assertSame($product->id, $review->product_id);
    }

    public function test_only_one_review_per_product(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();
        $this->orderFor($customer, $product);

        $this->send($customer, $product)->assertSessionHas('success');
        $this->send($customer, $product, ['body' => 'A second, different review text.'])->assertSessionHas('error');

        $this->assertDatabaseCount('product_reviews', 1);
    }

    public function test_bad_details_are_refused(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);   // several requests in a row; the limit has its own test

        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();
        $this->orderFor($customer, $product);

        foreach ([
            ['rating' => ''], ['rating' => 0], ['rating' => 6], ['rating' => 'five'], ['rating' => 3.5],
            ['body' => ''], ['body' => 'short'], ['body' => str_repeat('a', 1501)],
            ['title' => str_repeat('a', 101)],
        ] as $bad) {
            $this->send($customer, $product, $bad)->assertSessionHasErrors(array_key_first($bad));
        }

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_odd_input_never_breaks_the_page(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();
        $this->orderFor($customer, $product);

        $this->actingAs($customer)
            ->post(route('products.reviews.store', $product->slug), ['rating' => ['5'], 'title' => ['x'], 'body' => ['y']])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_one_link_is_fine_but_a_pile_of_links_is_refused(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();
        $this->orderFor($customer, $product);

        $this->send($customer, $product, ['body' => 'Lovely, see https://example.com for a photo'])->assertSessionHasNoErrors();
        ProductReview::query()->delete();
        $this->send($customer, $product, ['body' => 'Buy http://a.com and http://b.com and www.c.com now'])->assertSessionHasErrors('body');
    }

    public function test_the_spam_trap_saves_nothing(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();
        $this->orderFor($customer, $product);

        $this->send($customer, $product, ['website' => 'http://spam.example'])->assertSessionHas('success');

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_a_draft_product_cannot_be_reviewed(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct(['status' => 'draft']);
        $this->orderFor($customer, $product);

        $this->send($customer, $product)->assertNotFound();
    }

    public function test_the_sixth_review_in_ten_minutes_is_blocked(): void
    {
        $customer = $this->makeCustomer();

        for ($i = 1; $i <= 5; $i++) {
            $p = $this->makeProduct();
            $this->orderFor($customer, $p);
            $this->send($customer, $p)->assertRedirect();
        }

        $last = $this->makeProduct();
        $this->orderFor($customer, $last);
        $this->send($customer, $last)->assertStatus(429);
    }

    // ---------- what the product page shows ----------

    public function test_only_approved_reviews_are_shown_with_the_average_and_a_short_name(): void
    {
        $product = $this->makeProduct();
        $sara    = $this->makeCustomer(['name' => 'Sara Khan', 'email' => 'sara.secret@example.com']);
        $omar    = $this->makeCustomer(['name' => 'Omar Ali']);
        $lina    = $this->makeCustomer(['name' => 'Lina Raza']);
        $zed     = $this->makeCustomer(['name' => 'Zed Hidden']);

        $this->makeReview($product, $sara, ['rating' => 5, 'body' => 'Approved five stars']);
        $this->makeReview($product, $omar, ['rating' => 4, 'body' => 'Approved four stars']);
        $this->makeReview($product, $lina, ['rating' => 1, 'body' => 'Pending one star', 'status' => 'pending']);
        $this->makeReview($product, $zed, ['rating' => 1, 'body' => 'Rejected one star', 'status' => 'rejected']);

        $this->get(route('products.show', $product->slug))->assertOk()
            ->assertSee('Customer reviews (2)')
            ->assertSee('Approved five stars')
            ->assertSee('Approved four stars')
            ->assertSee('4.5')
            ->assertSee('Sara K.')
            ->assertSee('Verified buyer')
            ->assertDontSee('Pending one star')
            ->assertDontSee('Rejected one star')
            ->assertDontSee('sara.secret@example.com');
    }

    public function test_html_in_a_review_is_shown_as_plain_text(): void
    {
        $product  = $this->makeProduct();
        $customer = $this->makeCustomer(['name' => '<b>Evil</b> Person']);
        $this->makeReview($product, $customer, ['title' => '<i>Hi</i>', 'body' => '<script>alert(1)</script> nice thing']);

        $this->get(route('products.show', $product->slug))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<i>Hi</i>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_the_page_shows_the_form_only_to_a_buyer_who_has_not_reviewed_yet(): void
    {
        $product = $this->makeProduct();
        $buyer   = $this->makeCustomer();
        $other   = $this->makeCustomer();
        $this->orderFor($buyer, $product);

        $this->get(route('products.show', $product->slug))->assertOk()
            ->assertSee('Log in</a> to write a review', false)
            ->assertDontSee('name="rating"', false);

        $this->actingAs($other)->get(route('products.show', $product->slug))->assertOk()
            ->assertSee('Only customers who have received this product can review it.')
            ->assertDontSee('name="rating"', false);

        $this->actingAs($buyer)->get(route('products.show', $product->slug))->assertOk()
            ->assertSee('Write a review')
            ->assertSee('name="rating"', false);

        $this->send($buyer, $product);

        $this->actingAs($buyer)->get(route('products.show', $product->slug))->assertOk()
            ->assertSee('Thank you for your review')->assertDontSee('name="rating"', false);
    }

    public function test_deleting_a_product_deletes_its_reviews(): void
    {
        $product = $this->makeProduct();
        $this->makeReview($product, $this->makeCustomer());

        $product->delete();

        $this->assertDatabaseCount('product_reviews', 0);
    }

    // ---------- the admin page ----------

    public function test_staff_can_approve_and_reject_but_only_an_admin_can_delete(): void
    {
        $product = $this->makeProduct();
        $review  = $this->makeReview($product, $this->makeCustomer(), ['status' => 'pending']);
        $staff   = $this->makeStaff();

        $this->actingAs($staff)->patch(route('admin.reviews.approve', $review))->assertSessionHas('success');
        $this->assertSame('approved', $review->fresh()->status);

        $this->actingAs($staff)->patch(route('admin.reviews.reject', $review))->assertSessionHas('success');
        $this->assertSame('rejected', $review->fresh()->status);

        $this->actingAs($staff)->delete(route('admin.reviews.destroy', $review))->assertForbidden();
        $this->assertDatabaseHas('product_reviews', ['id' => $review->id]);

        $this->actingAs($this->makeAdmin())->delete(route('admin.reviews.destroy', $review))->assertSessionHas('success');
        $this->assertDatabaseMissing('product_reviews', ['id' => $review->id]);
    }

    public function test_the_admin_list_filters_by_status_and_is_staff_only(): void
    {
        $product = $this->makeProduct();
        $this->makeReview($product, $this->makeCustomer(), ['body' => 'Waiting remark here', 'status' => 'pending']);
        $this->makeReview($product, $this->makeCustomer(), ['body' => 'Public remark here', 'status' => 'approved']);

        $this->actingAs($this->makeStaff())->get(route('admin.reviews.index', ['status' => 'pending']))
            ->assertOk()->assertSee('Waiting remark here')->assertDontSee('Public remark here');

        $this->actingAs($this->makeCustomer())->get(route('admin.reviews.index'))->assertForbidden();
        $this->actingAs($this->makeCustomer())
            ->patch(route('admin.reviews.approve', ProductReview::first()))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.reviews.index'))->assertRedirect(route('login'));
    }
}