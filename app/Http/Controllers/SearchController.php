<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        // SECURITY: only plain text is accepted (a trick like ?q[]=x is ignored), at most 100 characters
        $raw  = $request->query('q');
        $term = is_string($raw) ? Str::limit(Str::squish($raw), 100, '') : '';

        $products = null;   // stays empty until the word is long enough

        if (mb_strlen($term) >= 2) {
            // % and _ are wildcards in a search. Typed by a visitor, they are treated as normal letters.
            $like = '%' . addcslashes($term, '\\%_') . '%';

            $products = Product::select(['id', 'category_id', 'name', 'slug', 'price', 'stock', 'image'])
                ->with('category:id,name')
                ->visible()
                ->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('category', fn($c) => $c->where('name', 'like', $like));
                })
                // Name matches come first, then the newest
                ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$like])
                ->latest('id')
                ->paginate(12)
                ->withQueryString();
        }

        $categories = Cache::remember('shop.categories', 3600, function () {
            return Category::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug']);
        });

        return view('search.index', compact('term', 'products', 'categories'));
    }
}
