<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;

class HomeController extends Controller
{
    public function index()
    {
        $columns = ['id', 'category_id', 'name', 'slug', 'price', 'stock', 'image'];

        // Up to 4 visible, featured products
        $featuredProducts = Product::select($columns)
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

        // How many visible products each of those categories has (one query for all of them)
        $categoryCounts = Product::visible()
            ->whereIn('category_id', $homeCategories->pluck('id'))
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        // The newest visible products that are not already shown as featured
        $newArrivals = Product::select($columns)
            ->with('category:id,name')
            ->visible()
            ->whereNotIn('id', $featuredProducts->pluck('id'))
            ->latest()
            ->take(4)
            ->get();

        // The latest approved reviews of products that can still be bought
        $homeReviews = ProductReview::approved()
            ->whereHas('product', fn ($q) => $q->visible())
            ->with(['user:id,name', 'product:id,name,slug'])
            ->latest('id')
            ->take(3)
            ->get();

        // The 3 newest live blog posts
        $latestPosts = BlogPost::published()->forCards()
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('home', compact(
            'featuredProducts', 'homeCategories', 'categoryCounts', 'newArrivals', 'homeReviews', 'latestPosts'
        ));
    }
}