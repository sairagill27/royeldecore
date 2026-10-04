<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        // Up to 4 active, featured products. with('category') loads each
        // product's category in one extra query instead of one per product.
        $featuredProducts = Product::with('category')
            ->where('status', 'active')
            ->where('is_featured', true)
            ->latest()
            ->take(4)
            ->get();

        return view('home', compact('featuredProducts'));
    }
}
