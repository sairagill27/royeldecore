<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    // Show the checkout page
    public function index(CartService $cart, CheckoutService $checkout)
    {
        $items = $cart->items();

        if (count($items) === 0) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $subtotal = $cart->subtotal($items);
        $shipping = $checkout->shippingFor($subtotal);
        $total    = $subtotal + $shipping;

        return view('checkout.index', compact('items', 'subtotal', 'shipping', 'total'));
    }

    // Place the order
    public function store(Request $request, CheckoutService $checkout)
    {
        // SECURITY: every value is checked on the server
        $data = $request->validate([
            'customer_name'        => ['required', 'string', 'max:100'],
            'customer_email'       => ['required', 'string', 'email', 'max:255'],
            'customer_phone'       => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'shipping_address'     => ['required', 'string', 'max:255'],
            'shipping_city'        => ['required', 'string', 'max:100'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_country'     => ['required', 'string', 'max:100'],
            'notes'                => ['nullable', 'string', 'max:500'],
        ], [
            'customer_phone.regex' => 'Please enter a valid phone number (numbers, spaces, + and - only).',
        ]);

        try {
            $order = $checkout->placeOrder($request->user(), $data);
        } catch (CheckoutException $e) {
            // Safe message (for example "only 2 left"): show it and keep what was typed
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        return redirect()
            ->route('account.orders.show', $order->order_number)
            ->with('placed', true);
    }
}
