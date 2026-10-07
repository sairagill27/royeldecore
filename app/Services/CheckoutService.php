<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    // Delivery fee for a given subtotal (rules are in config/shop.php)
    public function shippingFor(float $subtotal): float
    {
        $freeOver = (float) config('shop.free_shipping_over');

        if ($freeOver > 0 && $subtotal >= $freeOver) {
            return 0.0;
        }

        return (float) config('shop.shipping_fee');
    }

    // Creates the order, saves the items and reduces the stock.
    // Everything happens in ONE database transaction: if anything fails,
    // nothing is saved (no half-finished orders, no lost stock).
    public function placeOrder(User $user, array $data): Order
    {
        $cart = session('cart', []);

        if (! $cart) {
            throw new CheckoutException('Your cart is empty.');
        }

        $order = DB::transaction(function () use ($user, $data, $cart) {

            // lockForUpdate: while this order is being placed, other buyers must
            // wait for these product rows. This prevents selling the same last
            // item twice and prevents negative stock.
            $products = Product::whereIn('id', array_keys($cart))
                ->where('status', 'active')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines    = [];
            $subtotal = 0;

            foreach ($cart as $id => $qty) {
                $product = $products->get($id);
                $qty     = (int) $qty;

                if (! $product) {
                    throw new CheckoutException('A product in your cart is no longer available. Please review your cart.');
                }
                if ($product->stock < $qty) {
                    throw new CheckoutException('Sorry, only ' . $product->stock . ' of "' . $product->name . '" left in stock. Please update your cart.');
                }

                // The price always comes from the DATABASE, never from the browser
                $lineTotal = round($product->price * $qty, 2);
                $subtotal += $lineTotal;
                $lines[]   = [$product, $qty, $lineTotal];
            }

            $shipping = $this->shippingFor($subtotal);

            $order = Order::create([
                'user_id'              => $user->id,
                'order_number'         => $this->newOrderNumber(),
                'subtotal'             => $subtotal,
                'shipping_fee'         => $shipping,
                'total'                => round($subtotal + $shipping, 2),
                'customer_name'        => $data['customer_name'],
                'customer_email'       => $data['customer_email'],
                'customer_phone'       => $data['customer_phone'],
                'shipping_address'     => $data['shipping_address'],
                'shipping_city'        => $data['shipping_city'],
                'shipping_postal_code' => $data['shipping_postal_code'] ?? null,
                'shipping_country'     => $data['shipping_country'],
                'notes'                => $data['notes'] ?? null,
                'payment_method'       => 'cod',   // fixed on the server (cash on delivery)
            ]);

            foreach ($lines as [$product, $qty, $lineTotal]) {
                // Save a COPY of the product details
                $order->items()->create([
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'sku'          => $product->sku,
                    'price'        => $product->price,
                    'quantity'     => $qty,
                    'line_total'   => $lineTotal,
                ]);

                // Reduce the stock: 10 in stock, customer buys 2, 8 remain
                $product->decrement('stock', $qty);
            }

            return $order;
        });

        // Only after everything succeeded, empty the cart
        session()->forget('cart');

        return $order;
    }

    // Example: SGH-261006-K7M2Q (prefix, date, random letters). Always unique.
    private function newOrderNumber(): string
    {
        do {
            $number = config('shop.order_prefix') . '-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
