<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        // Up to 4 visible, featured products
        $featuredProducts = Product::select(['id', 'category_id', 'name', 'slug', 'price', 'stock', 'image'])
            ->with('category:id,name')
            ->visible()
            ->where('is_featured', true)
            ->latest()
            ->take(4)
            ->get();

        // The category cards: real categories, in the order you set in the admin panel
        $homeCategories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->take(6)
            ->get(['id', 'name', 'slug', 'image']);

        // The 3 newest live blog posts
        $latestPosts = BlogPost::published()->forCards()
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('home', compact('featuredProducts', 'homeCategories', 'latestPosts'));
    }
}
