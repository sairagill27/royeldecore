<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        // SECURITY: only active products can be opened. Draft products show 404.
        $product = Product::with('category')
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // Up to 4 other active products from the same category
        $related = Product::select(['id', 'category_id', 'name', 'slug', 'price', 'stock', 'image'])
            ->with('category:id,name')
            ->where('status', 'active')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->latest()
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'related'));
    }
}
