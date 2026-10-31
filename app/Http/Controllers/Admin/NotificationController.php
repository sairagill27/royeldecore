<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;

class NotificationController extends Controller
{
    // Everything that needs the owner's attention, worked out live from the database
    public function index()
    {
        $threshold = (int) config('shop.low_stock', 5);
        $since     = now()->subDays(7);

        return view('admin.notifications.index', [
            'threshold'    => $threshold,
            'messageCount' => ContactMessage::whereNull('read_at')->count(),
            'messages'     => ContactMessage::whereNull('read_at')->latest('id')->take(5)->get(),
            'commentCount' => Comment::where('status', 'pending')->count(),
            'comments'     => Comment::with('post:id,title')->where('status', 'pending')->latest('id')->take(5)->get(),
            'reviewCount'  => ProductReview::where('status', 'pending')->count(),
            'reviews'      => ProductReview::with('product:id,name')->where('status', 'pending')->latest('id')->take(5)->get(),
            'orderCount'   => Order::where('created_at', '>=', $since)->count(),
            'orders'       => Order::where('created_at', '>=', $since)->latest('id')->take(5)->get(),
            'lowStock'     => Product::visible()->where('stock', '<=', $threshold)->orderBy('stock')->take(8)->get(['id', 'name', 'stock']),
        ]);
    }
}