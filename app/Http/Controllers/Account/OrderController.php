<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // The customer's own orders (newest first)
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->withCount('items')
            ->latest()
            ->paginate(10);

        return view('account.orders.index', compact('orders'));
    }

    // One order
    public function show(Request $request, Order $order)
    {
        // SECURITY: a customer can only open THEIR OWN orders.
        // Someone else's order number shows a 404 page.
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load('items');

        return view('account.orders.show', compact('order'));
    }
}
