<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductGalleryController extends Controller
{
    // The most extra photos one product can have
    public const LIMIT = 8;

    // Add several photos at once
    public function store(Request $request, Product $product)
    {
        // SECURITY: only real JPG, PNG or WebP files (checked from the file itself), at most 2 MB each
        $request->validate([
            'photos'   => ['required', 'array', 'min:1', 'max:' . self::LIMIT],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
        ], [
            'photos.required'     => 'Please choose at least one photo.',
            'photos.array'        => 'Please choose the photos again.',
            'photos.max'          => 'You can add up to ' . self::LIMIT . ' photos at a time.',
            'photos.*.mimes'      => 'Each photo must be a JPG, PNG or WebP file.',
            'photos.*.max'        => 'Each photo must be smaller than 2 MB.',
            'photos.*.dimensions' => 'A photo is too large. Please use photos up to 6000 pixels wide or tall.',
        ]);

        // (queried without any ordering, so the counts also work on a strict MySQL)
        $existing = ProductImage::where('product_id', $product->id)->count();
        $room     = max(0, self::LIMIT - $existing);
        $files    = $request->file('photos');

        if (count($files) > $room) {
            return back()->withErrors([
                'photos' => $room > 0
                    ? 'This product has room for ' . $room . ' more ' . Str::plural('photo', $room) . '.'
                    : 'This product already has the maximum of ' . self::LIMIT . ' extra photos. Remove one first.',
            ]);
        }

        $order = (int) ProductImage::where('product_id', $product->id)->max('sort_order');

        foreach ($files as $file) {
            ProductImage::create([
                'product_id' => $product->id,
                // saved under a random name, never under the visitor's file name
                'path'       => $file->store('products/gallery', 'public'),
                'sort_order' => ++$order,
            ]);
        }

        return redirect()->route('admin.products.edit', $product)
            ->with('success', count($files) . ' ' . Str::plural('photo', count($files)) . ' added to the gallery.')
            ->with('alert_title', 'Gallery updated');
    }

    // Remove one photo (the file and its row)
    public function destroy(Product $product, ProductImage $image)
    {
        // A photo can only be removed through the product it belongs to
        abort_unless($image->product_id === $product->id, 404);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'The photo was removed.')
            ->with('alert_title', 'Photo removed');
    }
}