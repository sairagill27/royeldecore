<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_loads(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_the_shop_shows_active_products_and_hides_drafts(): void
    {
        $this->makeProduct(['name' => 'Visible Mirror']);
        $this->makeProduct(['name' => 'Hidden Draft', 'status' => 'draft']);

        $this->get('/shop')->assertOk()->assertSee('Visible Mirror')->assertDontSee('Hidden Draft');
    }

    public function test_a_category_page_shows_only_its_own_products(): void
    {
        $this->makeProduct(['name' => 'In The First']);
        $other = Category::create(['name' => 'Other', 'slug' => 'other', 'is_active' => true, 'sort_order' => 2]);
        $this->makeProduct(['name' => 'In The Other', 'category_id' => $other->id]);

        $this->get('/shop/test-decor')->assertOk()->assertSee('In The First')->assertDontSee('In The Other');
    }

    public function test_an_unknown_category_shows_404(): void
    {
        $this->get('/shop/does-not-exist')->assertNotFound();
    }

    public function test_the_price_filter_works(): void
    {
        $this->makeProduct(['name' => 'Cheap Item', 'price' => 500]);
        $this->makeProduct(['name' => 'Pricey Item', 'price' => 5000]);

        $this->get('/shop?min=1000')->assertSee('Pricey Item')->assertDontSee('Cheap Item');
    }

    public function test_junk_in_the_web_address_does_not_break_the_shop(): void
    {
        $this->makeProduct();

        $this->get('/shop?sort=drop%20table&min=abc&max=%3Cscript%3E&in_stock=maybe')->assertOk();
    }

    public function test_an_active_product_page_opens_but_a_draft_shows_404(): void
    {
        $active = $this->makeProduct(['name' => 'Gold Clock']);
        $draft  = $this->makeProduct(['status' => 'draft']);

        $this->get('/products/' . $active->slug)->assertOk()->assertSee('Gold Clock');
        $this->get('/products/' . $draft->slug)->assertNotFound();
    }
}
