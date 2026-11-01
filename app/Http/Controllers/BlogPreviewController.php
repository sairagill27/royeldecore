<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Product;
use App\Services\Markdown;

class BlogPreviewController extends Controller
{
    // /preview/blog/{post}: shows an article as visitors will see it, drafts included.
    // The "signed" middleware on the route has already checked the signature and the expiry time.
    public function __invoke(BlogPost $post)
    {
        $post->load(['category:id,name,slug', 'author:id,name', 'tags:id,name,slug']);

        // Is it live for visitors right now? (worked out BEFORE the changes below)
        $live = $post->status === 'published' && $post->published_at && $post->published_at->isPast();

        // In memory only, never saved: a draft has no date yet, and a preview must never be indexed
        $post->published_at = $post->published_at ?? now();
        $post->noindex      = true;

        $article = Markdown::article($post->content);

        $related = BlogPost::published()->forCards()
            ->where('id', '!=', $post->id)
            ->when($post->blog_category_id, fn ($q) => $q->where('blog_category_id', $post->blog_category_id))
            ->latest('published_at')
            ->take(3)
            ->get();

        $products = Product::select(['id', 'category_id', 'name', 'slug', 'price', 'stock', 'image'])
            ->with('category:id,name')
            ->visible()
            ->where('is_featured', true)
            ->latest()
            ->take(4)
            ->get();

        return response()
            ->view('blog.show', [
                'post'           => $post,
                'html'           => $article['html'],
                'toc'            => $article['toc'],
                'related'        => $related,
                'products'       => $products,
                'preview'        => true,
                'previewNotLive' => ! $live,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}