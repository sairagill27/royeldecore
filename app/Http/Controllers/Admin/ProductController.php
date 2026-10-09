<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // ---------- LIST: search, filters, pagination ----------
    public function index(Request $request)
    {
        $query = Product::select(['id', 'category_id', 'name', 'slug', 'sku', 'image', 'price', 'stock', 'status', 'is_featured'])
            ->with('category:id,name')
            ->latest('id');

        // Search by name or SKU (the value is bound safely, never pasted into SQL)
        $search = trim((string) $request->query('q'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Only these filter values are accepted
        if (in_array($request->query('status'), ['active', 'draft'], true)) {
            $query->where('status', $request->query('status'));
        }
        if (is_numeric($request->query('category'))) {
            $query->where('category_id', (int) $request->query('category'));
        }
        match ($request->query('stock')) {
            'low'   => $query->where('stock', '>', 0)->where('stock', '<=', 5),
            'out'   => $query->where('stock', 0),
            default => null,
        };

        $products   = $query->paginate(15)->withQueryString();
        $categories = $this->categories();

        return view('admin.products.index', compact('products', 'categories'));
    }

    // ---------- ADD ----------
    public function create()
    {
        // A blank product with starting values for the form
        $product    = new Product(['status' => 'draft', 'stock' => 0, 'is_featured' => false]);
        $categories = $this->categories();

        return view('admin.products.create', compact('product', 'categories'));
    }

    public function store(ProductRequest $request)
    {
        $product = Product::create($this->payload($request));

        return redirect()->route('admin.products.index')
            ->with('success', '"' . $product->name . '" was added.');
    }

    // ---------- EDIT ----------
    public function edit(Product $product)
    {
        $categories = $this->categories();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $product->update($this->payload($request, $product));

        return redirect()->route('admin.products.index')
            ->with('success', '"' . $product->name . '" was updated.');
    }

    // ---------- DELETE (admin only) ----------
    public function destroy(Request $request, Product $product)
    {
        // Staff can add and edit products, but only an admin can delete them
        abort_unless($request->user()->isAdmin(), 403);

        // Old orders keep their saved copy of the name and price (product_id just becomes empty)
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $name = $product->name;
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', '"' . $name . '" was deleted.');
    }

    // ---------- helpers ----------

    private function categories()
    {
        return Category::orderBy('sort_order')->get(['id', 'name']);
    }

    // Turns the checked form values into what is saved in the database
    private function payload(ProductRequest $request, ?Product $product = null): array
    {
        // Everything validated, except the two upload helper fields
        $data = $request->safe()->except(['image', 'remove_image']);

        $data['is_featured'] = $request->boolean('is_featured');

        // No slug typed? Build one from the name and make it unique
        $data['slug'] = filled($data['slug'] ?? null)
            ? $data['slug']
            : $this->uniqueSlug($data['name'], $product?->id);

        // PHOTO. store() saves the file under a random safe name, such as
        // products/Xk3j...q.jpg. The visitor's own file name is never used.
        if ($request->hasFile('image')) {
            if ($product?->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($product && $request->boolean('remove_image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = null;
        }

        return $data;
    }

    // "Wall Clock" becomes wall-clock; if taken, wall-clock-2, wall-clock-3 ...
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i    = 2;

        while (Product::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
