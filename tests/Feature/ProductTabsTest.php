<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTabsTest extends TestCase
{
    use RefreshDatabase;

    private function page($product)
    {
        return $this->get(route('products.show', $product->slug));
    }

    public function test_the_page_has_three_tabs_with_their_panes(): void
    {
        $product = $this->makeProduct();

        $this->page($product)->assertOk()
            ->assertSee('id="pdTabs"', false)
            ->assertSee('data-bs-toggle="tab"', false)
            ->assertSee('Description')
            ->assertSee('Product details')
            ->assertSee('Delivery and payment')
            ->assertSee('id="pd-pane-description"', false)
            ->assertSee('id="pd-pane-details"', false)
            ->assertSee('id="pd-pane-delivery"', false);
    }

    public function test_the_description_is_shown_as_plain_text_with_line_breaks(): void
    {
        $product = $this->makeProduct(['description' => "First line\n<script>alert(1)</script>"]);

        $this->page($product)->assertOk()
            ->assertSee('First line')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_a_product_without_a_description_says_so(): void
    {
        $product = $this->makeProduct(['description' => '']);

        $this->page($product)->assertOk()->assertSee('No description yet.');
    }

    public function test_the_details_tiles_show_sku_category_availability_and_payment(): void
    {
        $product = $this->makeProduct(['sku' => 'LMP-9', 'stock' => 3]);

        $this->page($product)->assertOk()
            ->assertSee('class="sgh-pd-spec-grid"', false)
            ->assertSee('LMP-9')
            ->assertSee('Availability')
            ->assertSee('Only 3 left')
            ->assertSee('Payment');
    }

    public function test_the_delivery_tab_and_the_one_line_note_say_cash_on_delivery(): void
    {
        config(['shop.currency' => 'Rs.', 'shop.shipping_fee' => 250, 'shop.free_shipping_over' => 15000]);

        $this->page($this->makeProduct())->assertOk()
            ->assertSee('class="sgh-pd-cod"', false)
            ->assertSee('Cash on delivery. Pay when your order arrives.')
            ->assertSee('Delivery charge Rs. 250')
            ->assertSee('no charge on orders over Rs. 15,000')
            ->assertSee('Track your order');
    }

    public function test_the_home_page_no_longer_has_the_ordering_section(): void
    {
        $this->get('/')->assertOk()
            ->assertDontSee('How ordering works')
            ->assertDontSee('Choose your pieces')
            ->assertDontSee('Delivery charge:');
    }
}