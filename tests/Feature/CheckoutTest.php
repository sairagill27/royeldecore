<?php

namespace Tests\Feature;

use App\Mail\NewOrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fixed delivery rules, so the sums below do not depend on your real settings
        config(['shop.shipping_fee' => 250, 'shop.free_shipping_over' => 0]);
        Mail::fake();   // no real emails
    }

    public function test_a_visitor_must_log_in_to_check_out(): void
    {
        $this->get('/checkout')->assertRedirect(route('login'));
        $this->post('/checkout', $this->checkoutData())->assertRedirect(route('login'));
    }

    public function test_an_empty_cart_goes_back_to_the_cart(): void
    {
        $this->actingAs($this->makeCustomer())->get('/checkout')->assertRedirect(route('cart.index'));
    }

    public function test_an_order_is_created_and_the_stock_goes_down(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct(['price' => 1000, 'stock' => 10]);

        $response = $this->actingAs($customer)
            ->withSession(['cart' => [$product->id => 2]])
            ->post('/checkout', $this->checkoutData());

        $order = Order::firstOrFail();

        $response->assertRedirect(route('account.orders.show', $order->order_number))
            ->assertSessionHas('placed', true)
            ->assertSessionMissing('cart');   // the cart is emptied

        $this->assertEquals(2000, $order->subtotal);
        $this->assertEquals(250, $order->shipping_fee);
        $this->assertEquals(2250, $order->total);
        $this->assertSame('pending', $order->status);
        $this->assertEquals(8, $product->fresh()->stock);   // 10 - 2

        // The order line keeps a COPY of the name and price
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_name' => $product->name,
            'quantity' => 2,
        ]);
    }

    public function test_delivery_is_free_above_the_free_shipping_amount(): void
    {
        config(['shop.free_shipping_over' => 1500]);
        $product = $this->makeProduct(['price' => 1000]);

        $this->actingAs($this->makeCustomer())
            ->withSession(['cart' => [$product->id => 2]])
            ->post('/checkout', $this->checkoutData());

        $this->assertEquals(0, Order::firstOrFail()->shipping_fee);
        $this->assertEquals(2000, Order::firstOrFail()->total);
    }

    public function test_the_browser_cannot_change_prices_or_status(): void
    {
        $product = $this->makeProduct(['price' => 1000]);

        $this->actingAs($this->makeCustomer())
            ->withSession(['cart' => [$product->id => 2]])
            ->post('/checkout', $this->checkoutData([
                'price' => 1,
                'total' => 1,
                'subtotal' => 1,
                'status' => 'delivered',
                'payment_method' => 'card',
            ]));

        $order = Order::firstOrFail();
        $this->assertEquals(2250, $order->total);
        $this->assertSame('pending', $order->status);
        $this->assertSame('cod', $order->payment_method);
    }

    public function test_the_last_piece_cannot_be_sold_twice(): void
    {
        $product = $this->makeProduct(['stock' => 1]);

        $this->actingAs($this->makeCustomer())
            ->withSession(['cart' => [$product->id => 2]])   // wants 2, only 1 exists
            ->post('/checkout', $this->checkoutData())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertEquals(1, $product->fresh()->stock);   // nothing was taken
    }

    public function test_a_product_hidden_after_adding_cannot_be_ordered(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->makeCustomer())
            ->withSession(['cart' => [$product->id => 1]]);
        $product->update(['status' => 'draft']);

        $this->post('/checkout', $this->checkoutData())->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_missing_or_bad_details_are_refused(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->makeCustomer())
            ->withSession(['cart' => [$product->id => 1]])
            ->post('/checkout', [])
            ->assertSessionHasErrors(['customer_name', 'customer_email', 'customer_phone', 'shipping_address', 'shipping_city']);

        $this->post('/checkout', $this->checkoutData(['customer_phone' => 'abc']))
            ->assertSessionHasErrors('customer_phone');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_emails_are_sent_to_the_customer_and_the_owner(): void
    {
        config(['shop.admin_email' => 'owner@example.com']);
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct();

        $this->actingAs($customer)
            ->withSession(['cart' => [$product->id => 1]])
            ->post('/checkout', $this->checkoutData(['customer_email' => 'buyer@example.com']));

        Mail::assertSent(OrderPlaced::class, fn($mail) => $mail->hasTo('buyer@example.com'));
        Mail::assertSent(NewOrderAlert::class, fn($mail) => $mail->hasTo('owner@example.com'));
    }

    public function test_a_broken_mail_server_never_breaks_an_order(): void
    {
        $product = $this->makeProduct();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP is down'));

        $this->actingAs($this->makeCustomer())
            ->withSession(['cart' => [$product->id => 1]])
            ->post('/checkout', $this->checkoutData())
            ->assertSessionHas('placed', true);

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_customers_can_only_see_their_own_orders(): void
    {
        $owner   = $this->makeCustomer();
        $product = $this->makeProduct();

        $this->actingAs($owner)->withSession(['cart' => [$product->id => 1]])
            ->post('/checkout', $this->checkoutData());
        $order = Order::firstOrFail();

        $this->actingAs($owner)->get(route('account.orders.show', $order->order_number))->assertOk();
        $this->actingAs($this->makeCustomer())->get(route('account.orders.show', $order->order_number))->assertNotFound();
    }
}
