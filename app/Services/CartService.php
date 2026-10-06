<?php

namespace App\Services;

use App\Models\Product;

class CartService
{
    // The cart is saved in the session as: [product_id => quantity]
    private const KEY = 'cart';

    private function raw(): array
    {
        return session(self::KEY, []);
    }

    // Add a product. Returns 'ok', 'limited' (cut to stock) or 'out' (no stock).
    public function add(Product $product, int $quantity): string
    {
        if ($product->stock < 1) {
            return 'out';
        }

        $cart   = $this->raw();
        $wanted = ($cart[$product->id] ?? 0) + $quantity;

        // Never allow more than the stock we have
        $cart[$product->id] = min($wanted, $product->stock);
        session([self::KEY => $cart]);

        return $wanted > $product->stock ? 'limited' : 'ok';
    }

    // Change a quantity. Returns 'ok' or 'limited'.
    public function update(Product $product, int $quantity): string
    {
        $cart = $this->raw();

        if (! isset($cart[$product->id])) {
            return 'ok';
        }
        if ($product->stock < 1) {
            unset($cart[$product->id]);
            session([self::KEY => $cart]);
            return 'ok';
        }

        $cart[$product->id] = min($quantity, $product->stock);
        session([self::KEY => $cart]);

        return $quantity > $product->stock ? 'limited' : 'ok';
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
        session([self::KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    // Total number of pieces (used by the header badge)
    public function count(): int
    {
        return (int) array_sum($this->raw());
    }

    // The cart lines with FRESH prices and stock from the database.
    // Products that were hidden or sold out are removed automatically.
    public function items(): array
    {
        $cart = $this->raw();
        if (! $cart) {
            return [];
        }

        $products = Product::with('category:id,name')
            ->whereIn('id', array_keys($cart))
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        $items = [];
        $clean = [];

        foreach ($cart as $id => $qty) {
            $product = $products->get($id);
            if (! $product || $product->stock < 1) {
                continue;
            }

            $qty        = min((int) $qty, $product->stock);
            $clean[$id] = $qty;
            $items[]    = [
                'product'    => $product,
                'quantity'   => $qty,
                'line_total' => round($product->price * $qty, 2),
            ];
        }

        session([self::KEY => $clean]);

        return $items;
    }

    public function subtotal(array $items): float
    {
        return round(array_sum(array_column($items, 'line_total')), 2);
    }
}
