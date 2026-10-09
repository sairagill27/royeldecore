<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    // ---------- LIST ----------
    public function index(Request $request)
    {
        $query = Order::withCount('items')->latest();

        // Only real status names are accepted
        $status = $request->query('status');
        if (is_string($status) && array_key_exists($status, Order::STATUSES)) {
            $query->where('status', $status);
        }

        // Search: order number, customer name, email or phone
        $search = trim((string) $request->query('q'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        // How many orders in each status (for the little counters)
        $counts = Order::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.orders.index', compact('orders', 'counts'));
    }

    // ---------- ONE ORDER ----------
    public function show(Order $order)
    {
        $order->load('items');

        return view('admin.orders.show', compact('order'));
    }

    // ---------- CHANGE STATUS ----------
    public function updateStatus(Request $request, Order $order, OrderStatusService $service)
    {
        // SECURITY: only real status names are accepted
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
        ]);

        try {
            $order = $service->change($order, $data['status'], $request->user()->id);
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = 'Order ' . $order->order_number . ' is now ' . $order->statusLabel() . '.';
        if ($order->status === 'cancelled') {
            $message .= ' The stock was returned.';
        }

        return back()->with('success', $message);
    }
}
