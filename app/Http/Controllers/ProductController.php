<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        // SECURITY: draft products, and products in a hidden category, show 404
        $product = Product::with('category')
            ->visible()
            ->where('slug', $slug)
            ->firstOrFail();

        // Up to 4 other visible products from the same category
        $related = Product::select(['id', 'category_id', 'name', 'slug', 'price', 'stock', 'image'])
            ->with('category:id,name')
            ->visible()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->latest()
            ->take(4)
            ->get();

        // Approved reviews only: the average and the count (the page and the search-engine data use them)
        $stats       = ProductReview::approved()->where('product_id', $product->id)
                           ->selectRaw('count(*) as total, avg(rating) as average')->first();
        $ratingCount = (int) ($stats->total ?? 0);
        $ratingAvg   = $ratingCount > 0 ? round((float) $stats->average, 1) : 0;

        return view('products.show', compact('product', 'related', 'ratingCount', 'ratingAvg'));
    }
}