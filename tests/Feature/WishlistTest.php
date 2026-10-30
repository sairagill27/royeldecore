<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    // Saves or removes a product the way the heart button does
    private function toggle($user, Product $product)
    {
        return $this->actingAs($user)->post(route('wishlist.toggle', $product->id));
    }

    // ---------- who can use it ----------

    public function test_visitors_are_sent_to_the_login_page(): void
    {
        $product = $this->makeProduct();

        $this->post(route('wishlist.toggle', $product->id))->assertRedirect(route('login'));
        $this->get(route('wishlist.index'))->assertRedirect(route('login'));

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    // ---------- saving and removing ----------

    public function test_a_customer_can_save_a_product_and_see_it_on_the_wishlist(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct(['name' => 'Brass Table Lamp']);

        $this->toggle($customer, $product)->assertRedirect();

        $this->assertDatabaseHas('wishlist_items', ['user_id' => $customer->id, 'product_id' => $product->id]);
        $this->actingAs($customer)->get(route('wishlist.index'))->assertOk()->assertSee('Brass Table Lamp');
    }

    public function test_pressing_the_heart_again_removes_it(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();

        $this->toggle($customer, $product);
        $this->toggle($customer, $product);

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_the_live_version_answers_with_json(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();

        $this->actingAs($customer)->postJson(route('wishlist.toggle', $product->id))->assertOk()->assertJson(['saved' => true]);
        $this->actingAs($customer)->postJson(route('wishlist.toggle', $product->id))->assertOk()->assertJson(['saved' => false]);
    }

    public function test_each_customer_only_sees_their_own_list(): void
    {
        $alice = $this->makeCustomer();
        $bob   = $this->makeCustomer();

        $this->toggle($alice, $this->makeProduct(['name' => 'Alice Mirror']));

        $this->actingAs($bob)->get(route('wishlist.index'))->assertOk()
            ->assertDontSee('Alice Mirror')
            ->assertSee('Your wishlist is empty');
    }

    // ---------- what can be saved ----------

    public function test_a_draft_product_cannot_be_saved(): void
    {
        $customer = $this->makeCustomer();
        $draft    = $this->makeProduct(['status' => 'draft']);

        $this->toggle($customer, $draft)->assertNotFound();

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_a_product_in_a_hidden_category_cannot_be_saved(): void
    {
        $hidden  = Category::create(['name' => 'Secret', 'slug' => 'secret', 'is_active' => false, 'sort_order' => 9]);
        $product = $this->makeProduct(['category_id' => $hidden->id]);

        $this->toggle($this->makeCustomer(), $product)->assertNotFound();

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_unknown_and_odd_product_numbers_show_404(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($customer)->post('/wishlist/99999')->assertNotFound();
        $this->actingAs($customer)->post('/wishlist/abc')->assertNotFound();
    }

    public function test_a_wishlist_holds_at_most_the_limit(): void
    {
        config(['shop.wishlist_limit' => 2]);
        $customer = $this->makeCustomer();

        $this->toggle($customer, $this->makeProduct());
        $this->toggle($customer, $this->makeProduct());
        $this->toggle($customer, $this->makeProduct())->assertSessionHas('error');

        $this->assertDatabaseCount('wishlist_items', 2);
    }

    // ---------- when a product changes later ----------

    public function test_a_product_that_became_unavailable_is_hidden_but_can_still_be_removed(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct(['name' => 'Gone Soon Vase']);

        $this->toggle($customer, $product);
        Product::whereKey($product->id)->update(['status' => 'draft']);

        $this->actingAs($customer)->get(route('wishlist.index'))->assertOk()
            ->assertDontSee('Gone Soon Vase')
            ->assertSee('no longer available');

        $this->toggle($customer, $product);   // removing still works
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_deleting_a_product_removes_it_from_every_wishlist(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();

        $this->toggle($customer, $product);
        $product->delete();

        $this->assertSame(0, WishlistItem::count());
    }

    // ---------- the heart on the pages ----------

    public function test_visitors_see_a_heart_that_leads_to_login_and_customers_see_a_working_one(): void
    {
        $product = $this->makeProduct(['name' => 'Heart Test Clock']);

        $this->get('/shop')->assertOk()
            ->assertSee('sgh-wish', false)
            ->assertSee('Log in to save to your wishlist');

        $this->actingAs($this->makeCustomer())->get('/shop')->assertOk()
            ->assertSee('wishlist/' . $product->id, false)
            ->assertSee('Save to wishlist');
    }

    public function test_a_saved_product_shows_a_filled_heart(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();

        $this->toggle($customer, $product);

        $this->actingAs($customer)->get('/shop')->assertOk()
            ->assertSee('bi-heart-fill', false)
            ->assertSee('Remove from wishlist');
    }

    public function test_the_account_menu_links_to_the_wishlist_and_the_page_is_not_for_search_engines(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($customer)->get('/')->assertOk()->assertSee('My Wishlist');
        $this->actingAs($customer)->get(route('wishlist.index'))->assertOk()->assertSee('noindex', false);
    }
}