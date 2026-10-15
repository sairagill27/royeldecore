<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ShopController extends Controller
{
    public function index(Request $request, ?Category $category = null)
    {
        // A hidden category behaves as if it does not exist
        abort_if($category && ! $category->is_active, 404);

        // SPEED: load only the columns the card needs, and the category name in one extra query
        $query = Product::select(['id', 'category_id', 'name', 'slug', 'price', 'stock', 'image'])
            ->with('category:id,name')
            ->visible();

        if ($category) {
            $query->where('category_id', $category->id);
        }

        // SECURITY: only real numbers are accepted; anything else is ignored
        $min = is_numeric($request->query('min')) ? max(0, (float) $request->query('min')) : null;
        $max = is_numeric($request->query('max')) ? max(0, (float) $request->query('max')) : null;
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }

        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0);
        }

        // SECURITY: only these sort options are allowed (anything else = newest)
        $sort = $request->query('sort', 'newest');
        match ($sort) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name'       => $query->orderBy('name'),
            default      => $query->latest(),
        };
        if (! in_array($sort, ['price_asc', 'price_desc', 'name'], true)) {
            $sort = 'newest';
        }

        $products = $query->paginate(12)->withQueryString();

        // SPEED: the category list rarely changes, so it is kept for 1 hour.
        // The admin category pages clear it whenever you change a category.
        $categories = Cache::remember('shop.categories', 3600, function () {
            return Category::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']);
        });

        return view('shop.index', compact('products', 'categories', 'category', 'sort'));
    }
}
