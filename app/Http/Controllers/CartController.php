<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Show the cart page
    public function index(CartService $cart)
    {
        $items    = $cart->items();
        $subtotal = $cart->subtotal($items);

        return view('cart.index', compact('items', 'subtotal'));
    }

    // Add a product (from the product page)
    public function add(Request $request, CartService $cart)
    {
        // SECURITY: check every value, never trust the browser
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        // Only active products can be added
        $product = Product::visible()->findOrFail($data['product_id']);

        // The toast shows a "View cart" button after a successful add
        $viewCart = ['label' => 'View cart', 'url' => route('cart.index')];

        return match ($cart->add($product, $data['quantity'])) {
            'out'     => back()->with('error', 'Sorry, this product is out of stock.'),
            'limited' => back()->with('warning', 'Only ' . $product->stock . ' available, so we adjusted the quantity.')
                ->with('toast_action', $viewCart),
            default   => back()->with('success', '"' . $product->name . '" was added to your cart.')
                ->with('toast_action', $viewCart),
        };
    }

    // Change a quantity (from the cart page)
    public function update(Request $request, Product $product, CartService $cart)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $result = $cart->update($product, $data['quantity']);

        return $result === 'limited'
            ? back()->with('error', 'Only ' . $product->stock . ' available for this product.')
            : back()->with('success', 'Cart updated.');
    }

    // Remove one product
    public function remove(Product $product, CartService $cart)
    {
        $cart->remove($product->id);

        return back()->with('success', 'Item removed.');
    }

    // Empty the whole cart
    public function clear(CartService $cart)
    {
        $cart->clear();

        return back()->with('success', 'Your cart is now empty.');
    }
}
