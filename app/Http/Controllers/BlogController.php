<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Product;
use App\Models\SlugRedirect;
use App\Models\Tag;
use App\Services\Markdown;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    // /blog
    public function index(Request $request)
    {
        return $this->listing($request);
    }

    // /blog/category/{slug}
    public function category(Request $request, BlogCategory $category)
    {
        return $this->listing($request, $category);
    }

    // /blog/tag/{slug}
    public function tag(Request $request, Tag $tag)
    {
        return $this->listing($request, null, $tag);
    }

    // One page that serves the blog home, a category, a tag and search results
    private function listing(Request $request, ?BlogCategory $category = null, ?Tag $tag = null)
    {
        // SECURITY: only plain text is accepted (a trick like ?q[]=x is ignored), at most 100 characters
        $raw  = $request->query('q');
        $term = is_string($raw) ? Str::limit(Str::squish($raw), 100, '') : '';
        if (mb_strlen($term) < 2) {
            $term = '';   // a one-letter search is ignored
        }

        // Only published posts, newest first
        $base = BlogPost::published()->forCards()->latest('published_at')->latest('id');

        if ($category) {
            $base->where('blog_category_id', $category->id);
        }
        if ($tag) {
            $base->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id));
        }

        if ($term !== '') {
            // % and _ are wildcards in a search. Typed by a visitor, they are treated as normal letters.
            $like = '%' . addcslashes($term, '\\%_') . '%';

            $base->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                  ->orWhere('excerpt', 'like', $like)
                  ->orWhere('content', 'like', $like)
                  ->orWhereHas('tags', fn ($t) => $t->where('name', 'like', $like))
                  ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like));
            });
        }

        // The featured article is the newest post. It is shown on page 1 of the plain blog home only,
        // and it is left out of the grid so it never appears twice.
        $featured   = null;
        $featuredId = null;
        $page       = max(1, $request->integer('page', 1));
        if (! $category && ! $tag && $term === '') {
            $newest     = (clone $base)->first();
            $featuredId = $newest?->id;
            if ($page <= 1) {
                $featured = $newest;
            }
        }

        $posts = $base
            ->when($featuredId, fn ($q) => $q->where('id', '!=', $featuredId))
            ->paginate(9)
            ->withQueryString();

        // The category pills: only categories that have at least one live post
        $categories = BlogCategory::withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($c) => $c->posts_count > 0);

        // The heading and the intro text
        if ($term !== '') {
            $heading = 'Results for "' . $term . '"';
            $intro   = 'Articles matching your search.';
        } elseif ($category) {
            $heading = $category->name;
            $intro   = 'Décor ideas and inspiration: ' . $category->name . '.';
        } elseif ($tag) {
            $heading = 'Tagged: ' . $tag->name;
            $intro   = 'Articles tagged "' . $tag->name . '".';
        } else {
            $heading = 'Décor ideas and inspiration';
            $intro   = 'Practical décor ideas, room inspiration and tips for every budget and space.';
        }

        // CANONICAL: the one official address of this page. Search words never count,
        // and page 2, 3... keep their own page number.
        if ($category) {
            $canonical = route('blog.category', $category->slug);
        } elseif ($tag) {
            $canonical = route('blog.tag', $tag->slug);
        } else {
            $canonical = route('blog.index');
        }
        if ($term === '' && $page > 1) {
            $canonical .= '?page=' . $page;
        }

        // Search results and empty pages are kept out of search engines
        $noindex = $term !== '' || ($posts->isEmpty() && ! $featured);

        return view('blog.index', compact(
            'posts', 'featured', 'categories', 'category', 'tag', 'term', 'heading', 'intro', 'noindex', 'canonical'
        ));
    }

    // /blog/{slug}: one article
    public function show(string $slug)
    {
        // SECURITY: drafts and scheduled posts are not found
        $post = BlogPost::published()
            ->with(['category:id,name,slug', 'author:id,name', 'tags:id,name,slug'])
            ->where('slug', $slug)
            ->first();

        if (! $post) {
            // An OLD address of a live article? Send visitors (and Google) to the new one.
            $redirect = SlugRedirect::where('old_slug', $slug)->first();
            $target   = $redirect
                ? BlogPost::published()->whereKey($redirect->blog_post_id)->first(['id', 'slug'])
                : null;

            abort_if(! $target, 404);

            return redirect()->route('blog.show', $target->slug, 301);
        }

        // The article text: raw HTML removed, unsafe links blanked, headings numbered for the contents box
        $article = Markdown::article($post->content);
        $html    = $article['html'];
        $toc     = $article['toc'];

        // "Keep reading": up to 3 posts from the same category, then the latest others
        $related = BlogPost::published()->forCards()
            ->where('id', '!=', $post->id)
            ->when($post->blog_category_id, fn ($q) => $q->where('blog_category_id', $post->blog_category_id))
            ->latest('published_at')
            ->take(3)
            ->get();

        if ($related->count() < 3) {
            $more = BlogPost::published()->forCards()
                ->where('id', '!=', $post->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->latest('published_at')
                ->take(3 - $related->count())
                ->get();

            $related = $related->concat($more);
        }

        // "From our shop": featured products (the same for every article for now)
        $products = Product::select(['id', 'category_id', 'name', 'slug', 'price', 'stock', 'image'])
            ->with('category:id,name')
            ->visible()
            ->where('is_featured', true)
            ->latest()
            ->take(4)
            ->get();

        return view('blog.show', compact('post', 'html', 'toc', 'related', 'products'));
    }
}