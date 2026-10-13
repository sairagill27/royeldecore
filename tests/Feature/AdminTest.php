<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['shop.shipping_fee' => 250, 'shop.free_shipping_over' => 0]);
        Mail::fake();
    }

    // A customer places a real order through checkout
    private function placeOrder(User $customer, Product $product, int $quantity = 2): Order
    {
        $this->actingAs($customer)
            ->withSession(['cart' => [$product->id => $quantity]])
            ->post('/checkout', $this->checkoutData());

        return Order::latest('id')->firstOrFail();
    }

    private function changeStatus(User $by, Order $order, string $status)
    {
        return $this->actingAs($by)->patch(route('admin.orders.status', $order->order_number), ['status' => $status]);
    }

    // ================= ORDERS =================

    public function test_an_order_moves_through_every_step_and_delivery_marks_it_paid(): void
    {
        $admin = $this->makeAdmin();
        $order = $this->placeOrder($this->makeCustomer(), $this->makeProduct());

        foreach (['confirmed', 'processing', 'shipped', 'delivered'] as $status) {
            $this->changeStatus($admin, $order, $status)->assertSessionHas('success');
            $this->assertSame($status, $order->fresh()->status);
        }

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_steps_cannot_be_skipped(): void
    {
        $order = $this->placeOrder($this->makeCustomer(), $this->makeProduct());

        $this->changeStatus($this->makeAdmin(), $order, 'delivered')->assertSessionHas('error');

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_cancelling_returns_the_stock_once(): void
    {
        $admin   = $this->makeAdmin();
        $product = $this->makeProduct(['stock' => 10]);
        $order   = $this->placeOrder($this->makeCustomer(), $product, 3);

        $this->assertEquals(7, $product->fresh()->stock);

        $this->changeStatus($admin, $order, 'cancelled');
        $this->assertEquals(10, $product->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status);

        // A cancelled order is final: cancelling again must not add stock twice
        $this->changeStatus($admin, $order, 'cancelled')->assertSessionHas('error');
        $this->changeStatus($admin, $order, 'confirmed')->assertSessionHas('error');
        $this->assertEquals(10, $product->fresh()->stock);
    }

    public function test_an_unknown_status_is_refused(): void
    {
        $order = $this->placeOrder($this->makeCustomer(), $this->makeProduct());

        $this->changeStatus($this->makeAdmin(), $order, 'hacked')->assertSessionHasErrors('status');
    }

    public function test_a_customer_cannot_change_an_order_status(): void
    {
        $customer = $this->makeCustomer();
        $order    = $this->placeOrder($customer, $this->makeProduct());

        $this->changeStatus($customer, $order, 'delivered')->assertForbidden();
        $this->assertSame('pending', $order->fresh()->status);
    }

    // ================= PRODUCTS =================

    private function productData(array $overrides = []): array
    {
        $category = Category::firstOrCreate(
            ['slug' => 'test-decor'],
            ['name' => 'Test Decor', 'is_active' => true, 'sort_order' => 1]
        );

        return array_merge([
            'name' => 'New Lamp',
            'category_id' => $category->id,
            'description' => 'Warm light.',
            'price' => 2500,
            'stock' => 5,
            'status' => 'active',
        ], $overrides);
    }

    public function test_an_admin_can_add_a_product_and_the_slug_is_made_for_them(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.store'), $this->productData())
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['name' => 'New Lamp', 'slug' => 'new-lamp', 'status' => 'active']);
    }

    public function test_bad_product_details_are_refused(): void
    {
        $admin = $this->makeAdmin();
        $this->makeProduct(['sku' => 'WD-001']);

        $this->actingAs($admin)->post(route('admin.products.store'), $this->productData(['sku' => 'WD-001']))
            ->assertSessionHasErrors('sku');                                     // duplicate SKU
        $this->actingAs($admin)->post(route('admin.products.store'), $this->productData(['price' => -5]))
            ->assertSessionHasErrors('price');                                   // negative price
        $this->actingAs($admin)->post(route('admin.products.store'), $this->productData(['stock' => -1]))
            ->assertSessionHasErrors('stock');                                   // negative stock
        $this->actingAs($admin)->post(route('admin.products.store'), $this->productData(['slug' => 'Bad Slug!']))
            ->assertSessionHasErrors('slug');                                    // not a clean web address

        $this->assertDatabaseCount('products', 1);   // only the one we made first
    }

    public function test_an_admin_can_update_price_and_stock(): void
    {
        $product = $this->makeProduct(['price' => 1000, 'stock' => 10]);

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.products.update', $product), $this->productData([
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => 1500,
                'stock' => 4,
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->assertEquals(1500, $product->fresh()->price);
        $this->assertEquals(4, $product->fresh()->stock);
    }

    public function test_a_file_that_is_not_an_image_is_refused(): void
    {
        Storage::fake('public');

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.store'), $this->productData([
                'image' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_a_real_photo_is_saved_under_a_safe_random_name(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('The PHP "gd" extension is off, so a test photo cannot be made.');
        }
        Storage::fake('public');

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.store'), $this->productData([
                'image' => UploadedFile::fake()->image('my photo.jpg', 600, 600),
            ]));

        $product = Product::firstOrFail();
        $this->assertStringStartsWith('products/', $product->image);
        $this->assertStringNotContainsString('my photo', $product->image);   // our name, not the visitor's
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_only_an_admin_can_delete_a_product(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->makeStaff())->delete(route('admin.products.destroy', $product))->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        $this->actingAs($this->makeAdmin())->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    // ================= CATEGORIES AND CUSTOMERS =================

    public function test_a_category_with_products_cannot_be_deleted(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.categories.destroy', $product->category))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $product->category_id]);
    }

    public function test_an_admin_can_block_and_unblock_a_customer(): void
    {
        $admin    = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $this->actingAs($admin)->patch(route('admin.customers.toggle', $customer));
        $this->assertFalse((bool) $customer->fresh()->is_active);

        $this->actingAs($admin)->patch(route('admin.customers.toggle', $customer));
        $this->assertTrue((bool) $customer->fresh()->is_active);
    }

    public function test_staff_cannot_block_customers(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($this->makeStaff())->patch(route('admin.customers.toggle', $customer))->assertForbidden();
        $this->assertTrue((bool) $customer->fresh()->is_active);
    }
}
