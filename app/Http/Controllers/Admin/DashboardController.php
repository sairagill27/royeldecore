<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $tz       = config('shop.timezone');
        $excluded = ['cancelled', 'refunded'];   // these do not count as revenue

        // ---------- Top statistic cards ----------
        $stats = [
            'products'  => Product::count(),
            'orders'    => Order::count(),
            'pending'   => Order::where('status', 'pending')->count(),
            'customers' => User::where('role', 'customer')->count(),
            'revenue'   => Order::whereNotIn('status', $excluded)->sum('total'),
        ];

        // ---------- Sales of the last 7 days (for the chart) ----------
        $start  = now($tz)->subDays(6)->startOfDay();
        $orders = Order::where('created_at', '>=', $start->copy()->utc())
            ->whereNotIn('status', $excluded)
            ->get(['total', 'created_at']);

        $sales = collect(range(6, 0))->map(function ($i) use ($orders, $tz) {
            $day = now($tz)->subDays($i);

            return [
                'label' => $day->format('D'),      // Mon, Tue...
                'date'  => $day->format('d M'),
                'total' => (float) $orders
                    ->filter(fn($o) => $o->created_at->copy()->timezone($tz)->isSameDay($day))
                    ->sum('total'),
            ];
        });

        $maxSale   = max(1, $sales->max('total'));   // tallest bar = 100%
        $weekTotal = $sales->sum('total');

        // ---------- Orders by status: ['pending' => 3, 'shipped' => 1 ...] ----------
        $statusCounts = Order::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentOrders = Order::latest()->take(5)->get();

        // Active products with 5 or fewer pieces left (lowest first)
        $lowStock = Product::where('status', 'active')
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->take(5)
            ->get(['id', 'name', 'stock']);

        return view('admin.dashboard', compact(
            'stats',
            'sales',
            'maxSale',
            'weekTotal',
            'statusCounts',
            'recentOrders',
            'lowStock'
        ));
    }
}
