<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // ---------- LIST ----------
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories'));
    }

    // ---------- ADD ----------
    public function create()
    {
        $category = new Category(['is_active' => true, 'sort_order' => 0]);

        return view('admin.categories.create', compact('category'));
    }

    public function store(CategoryRequest $request)
    {
        $category = Category::create($this->payload($request));
        $this->clearCache();

        return redirect()->route('admin.categories.index')
            ->with('success', '"' . $category->name . '" was added.');
    }

    // ---------- EDIT ----------
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($this->payload($request, $category));
        $this->clearCache();

        return redirect()->route('admin.categories.index')
            ->with('success', '"' . $category->name . '" was updated.');
    }

    // ---------- DELETE (admin only) ----------
    public function destroy(Request $request, Category $category)
    {
        abort_unless($request->user()->isAdmin(), 403);

        // A category that still has products cannot be deleted
        if ($category->products()->exists()) {
            return back()->with('error', '"' . $category->name . '" still has products. Move or delete them first, or hide the category instead.');
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
        $name = $category->name;
        $category->delete();
        $this->clearCache();

        return redirect()->route('admin.categories.index')
            ->with('success', '"' . $name . '" was deleted.');
    }

    // ---------- helpers ----------

    // The shop page keeps the category list for 1 hour. Clear it so changes show at once.
    private function clearCache(): void
    {
        Cache::forget('shop.categories');
    }

    private function payload(CategoryRequest $request, ?Category $category = null): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['slug']       = filled($data['slug'] ?? null)
            ? $data['slug']
            : $this->uniqueSlug($data['name'], $category?->id);

        // PHOTO: saved under a random safe name. The visitor's file name is never used.
        if ($request->hasFile('image')) {
            if ($category?->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        } elseif ($category && $request->boolean('remove_image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = null;
        }

        return $data;
    }

    // "Wall Clocks" becomes wall-clocks; if taken, wall-clocks-2 ...
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i    = 2;

        while (Category::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
