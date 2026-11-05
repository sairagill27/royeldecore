<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ProductReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    // An approved (or other) review straight in the database
    private function makeReview($product, $user, int $rating, string $status = 'approved'): ProductReview
    {
        $review = new ProductReview(['rating' => $rating, 'title' => null, 'body' => 'Lovely piece, exactly as described.']);
        $review->product_id = $product->id;
        $review->user_id    = $user->id;
        $review->status     = $status;
        $review->save();

        return $review;
    }

    private function page($product)
    {
        return $this->get(route('products.show', $product->slug));
    }

    // ---------- the look ----------

    public function test_the_page_has_the_green_strip_the_card_and_exactly_one_h1(): void
    {
        $product = $this->makeProduct(['name' => 'Gold Round Mirror', 'price' => 3500, 'stock' => 20]);

        $html = $this->page($product)->assertOk()
            ->assertSee('class="sgh-tool-head sgh-pd-strip"', false)
            ->assertSee('sgh-tool-leaves', false)
            ->assertSee('class="sgh-pd-card"', false)
            ->assertSee('Gold Round Mirror')
            ->assertSee('Add to Cart')
            ->assertSee('In stock')
            ->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_the_cart_form_still_sends_only_the_product_and_the_quantity(): void
    {
        $product = $this->makeProduct();

        $this->page($product)->assertOk()
            ->assertSee('id="cartForm"', false)
            ->assertSee('action="' . route('cart.add') . '"', false)
            ->assertSee('name="product_id" value="' . $product->id . '"', false)
            ->assertSee('name="quantity"', false)
            ->assertDontSee('name="price"', false);
    }

    public function test_stock_levels_show_the_right_flag_and_badge(): void
    {
        $this->page($this->makeProduct(['stock' => 3]))->assertOk()->assertSee('Only 3 left');

        $this->page($this->makeProduct(['stock' => 0]))->assertOk()
            ->assertSee('Out of stock')
            ->assertSee('"availability":"https://schema.org/OutOfStock"', false);

        $this->page($this->makeProduct(['stock' => 40]))->assertOk()
            ->assertDontSee('Only 40 left')
            ->assertSee('"availability":"https://schema.org/InStock"', false);
    }

    public function test_the_phone_bar_belongs_to_the_cart_form(): void
    {
        $this->page($this->makeProduct())->assertOk()
            ->assertSee('class="sgh-pd-sticky"', false)
            ->assertSee('form="cartForm"', false);
    }

    public function test_it_says_cash_on_delivery_and_shows_the_delivery_charge_from_the_config(): void
    {
        config(['shop.currency' => 'Rs.', 'shop.shipping_fee' => 250, 'shop.free_shipping_over' => 15000]);

        $this->page($this->makeProduct())->assertOk()
            ->assertSee('Cash on delivery')
            ->assertSee('Delivery charge Rs. 250')
            ->assertSee('no charge on orders over Rs. 15,000')
            ->assertSee('Track your order')
            ->assertDontSee('Free delivery')
            ->assertDontSee('Fast Delivery')
            ->assertDontSee('Easy Returns');
    }

    public function test_the_save_to_wishlist_button_and_the_reviews_section_are_still_there(): void
    {
        $this->page($this->makeProduct())->assertOk()
            ->assertSee('Save to wishlist')
            ->assertSee('id="reviews"', false)
            ->assertSee('Customer reviews (0)');
    }

    public function test_related_products_show_up(): void
    {
        $category = Category::create(['name' => 'Mirrors', 'slug' => 'mirrors', 'is_active' => true, 'sort_order' => 1]);
        $main     = $this->makeProduct(['category_id' => $category->id, 'name' => 'Main Piece']);
        $this->makeProduct(['category_id' => $category->id, 'name' => 'Sibling Piece']);

        $this->page($main)->assertOk()->assertSee('You may also like')->assertSee('Sibling Piece');
    }

    public function test_a_draft_product_is_still_not_found(): void
    {
        $this->page($this->makeProduct(['status' => 'draft']))->assertNotFound();
    }

    // ---------- WhatsApp and sharing ----------

    public function test_the_whatsapp_question_button_needs_a_valid_number(): void
    {
        $product = $this->makeProduct(['name' => 'Lamp']);

        config(['contact.whatsapp' => null]);
        $this->page($product)->assertOk()->assertDontSee('Ask on WhatsApp');

        config(['contact.whatsapp' => '123']);
        $this->page($product)->assertOk()->assertDontSee('Ask on WhatsApp');

        config(['contact.whatsapp' => '+92 300-1234567']);
        $this->page($product)->assertOk()
            ->assertSee('Ask on WhatsApp')
            ->assertSee('https://wa.me/923001234567?text=', false);
    }

    public function test_share_links_are_plain_links_and_names_are_encoded_in_them(): void
    {
        $product = $this->makeProduct(['name' => 'Tom & "Jerry" Clock']);

        $this->page($product)->assertOk()
            ->assertSee('https://wa.me/?text=Tom%20%26%20%22Jerry%22%20Clock', false)
            ->assertSee('https://www.facebook.com/sharer/sharer.php?u=', false)
            ->assertSee('id="copyLink"', false);
    }

    // ---------- search engines and sharing tags ----------

    public function test_the_page_has_a_canonical_link_sharing_tags_and_product_data(): void
    {
        $product = $this->makeProduct(['name' => 'Brass Lamp', 'price' => 3500, 'sku' => 'LMP-9', 'stock' => 10]);

        $this->page($product)->assertOk()
            ->assertSee('<link rel="canonical" href="' . route('products.show', $product->slug) . '">', false)
            ->assertSee('property="og:type" content="product"', false)
            ->assertSee('property="product:price:amount" content="3500.00"', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"price":"3500.00"', false)
            ->assertSee('"sku":"LMP-9"', false);
    }

    public function test_star_ratings_appear_in_the_data_only_for_real_approved_reviews(): void
    {
        $product = $this->makeProduct();

        $this->page($product)->assertOk()->assertDontSee('aggregateRating', false);

        $this->makeReview($product, $this->makeCustomer(), 5);
        $this->makeReview($product, $this->makeCustomer(), 4);
        $this->makeReview($product, $this->makeCustomer(), 1, 'pending');    // does not count
        $this->makeReview($product, $this->makeCustomer(), 1, 'rejected');   // does not count

        $this->page($product)->assertOk()
            ->assertSee('"ratingValue":4.5', false)
            ->assertSee('"reviewCount":2', false)
            ->assertSee('2 reviews');
    }

    // ---------- safety ----------

    public function test_a_product_name_can_never_run_as_code_anywhere_on_the_page(): void
    {
        $product = $this->makeProduct(['name' => '<script>alert(1)</script>']);

        $this->page($product)->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}