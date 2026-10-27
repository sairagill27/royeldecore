<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostImageController extends Controller
{
    // Uploads one picture for use INSIDE an article and returns its address.
    // Only JPG, PNG and WebP are accepted. The type is checked from the file itself,
    // and the file is saved under a random name, never under the visitor's file name.
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
        ], [
            'image.mimes'      => 'The photo must be a JPG, PNG or WebP file.',
            'image.max'        => 'The photo must be smaller than 2 MB.',
            'image.dimensions' => 'The photo is too large. Please use one up to 6000 pixels wide or tall.',
        ]);

        $path = $request->file('image')->store('blog/content', 'public');

        // A relative address, so it keeps working if the domain changes later
        return response()->json(['url' => '/storage/' . $path]);
    }
}