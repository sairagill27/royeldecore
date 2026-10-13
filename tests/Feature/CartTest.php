<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_can_be_added_to_the_cart(): void
    {
        $product = $this->makeProduct();

        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 2])
            ->assertSessionHas('cart', [$product->id => 2]);
    }

    public function test_adding_the_same_product_twice_adds_up(): void
    {
        $product = $this->makeProduct();

        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 2]);
        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 3]);

        $this->assertSame([$product->id => 5], session('cart'));
    }

    public function test_the_cart_never_holds_more_than_the_stock(): void
    {
        $product = $this->makeProduct(['stock' => 3]);

        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 5])
            ->assertSessionHas('cart', [$product->id => 3])
            ->assertSessionHas('warning');
    }

    public function test_a_sold_out_product_cannot_be_added(): void
    {
        $product = $this->makeProduct(['stock' => 0]);

        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1])
            ->assertSessionHas('error')
            ->assertSessionMissing('cart');
    }

    public function test_a_draft_product_cannot_be_added(): void
    {
        $product = $this->makeProduct(['status' => 'draft']);

        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertNotFound();
    }

    public function test_silly_quantities_are_refused(): void
    {
        $product = $this->makeProduct();

        foreach ([0, -1, 100, 'abc'] as $quantity) {
            $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => $quantity])
                ->assertSessionHasErrors('quantity');
        }
    }

    public function test_updating_the_quantity_is_capped_at_the_stock(): void
    {
        $product = $this->makeProduct(['stock' => 5]);

        $this->withSession(['cart' => [$product->id => 1]])
            ->patch(route('cart.update', $product), ['quantity' => 50])
            ->assertSessionHas('cart', [$product->id => 5]);
    }

    public function test_an_item_can_be_removed(): void
    {
        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 2]])
            ->delete(route('cart.remove', $product))
            ->assertSessionHas('cart', []);
    }

    public function test_the_cart_page_shows_the_items(): void
    {
        $product = $this->makeProduct(['name' => 'Brass Vase']);

        $this->withSession(['cart' => [$product->id => 1]])
            ->get('/cart')->assertOk()->assertSee('Brass Vase');
    }
}
