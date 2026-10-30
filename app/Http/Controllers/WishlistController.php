<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    // /account/wishlist: the saved products, newest first
    public function index(Request $request)
    {
        $ids = WishlistItem::where('user_id', $request->user()->id)->latest('id')->pluck('product_id');

        // Only products that can still be bought are shown
        $products = Product::select(['id', 'category_id', 'name', 'slug', 'price', 'stock', 'image'])
            ->with('category:id,name')
            ->visible()
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($product) => $ids->search($product->id))
            ->values();

        return view('wishlist.index', [
            'products'    => $products,
            'unavailable' => $ids->count() - $products->count(),
        ]);
    }

    // POST /wishlist/{productId}: saves the product, or removes it if it is already saved
    public function toggle(Request $request, int $productId)
    {
        $userId   = $request->user()->id;   // always the logged-in person, never a value from the form
        $existing = WishlistItem::where('user_id', $userId)->where('product_id', $productId)->first();

        if ($existing) {
            // Removing always works, even if the product has since been hidden
            $existing->delete();
            $saved = false;
        } else {
            // Only a product that is visible in the shop can be saved
            $product = Product::visible()->whereKey($productId)->first();
            abort_if(! $product, 404);

            $limit = (int) config('shop.wishlist_limit', 200);
            if (WishlistItem::where('user_id', $userId)->count() >= $limit) {
                $message = 'Your wishlist is full. Please remove something before adding more.';

                return $request->expectsJson()
                    ? response()->json(['message' => $message], 422)
                    : back()->with('error', $message)->with('alert_title', 'Wishlist full');
            }

            WishlistItem::firstOrCreate(['user_id' => $userId, 'product_id' => $product->id]);
            $saved = true;
        }

        return $request->expectsJson() ? response()->json(['saved' => $saved]) : back();
    }
}