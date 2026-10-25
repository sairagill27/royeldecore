<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\SlugRedirect;
use App\Models\Tag;
use App\Services\Markdown;
use App\Services\SeoScore;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    // ---------- LIST ----------
    public function index(Request $request)
    {
        // The article text is not loaded here: the list does not need it, so it stays fast
        $query = BlogPost::select(['id', 'user_id', 'blog_category_id', 'title', 'slug', 'featured_image', 'status', 'published_at', 'noindex', 'created_at'])
            ->with(['category:id,name', 'author:id,name'])
            ->latest('id');

        // Search in titles. % and _ typed by the admin are treated as normal letters.
        $search = trim((string) $request->query('q'));
        if ($search !== '') {
            $query->where('title', 'like', '%' . addcslashes($search, '\\%_') . '%');
        }

        // Only these status values are accepted
        $filter = in_array($request->query('status'), ['published', 'scheduled', 'draft'], true)
            ? $request->query('status')
            : null;

        match ($filter) {
            'published' => $query->published(),
            'scheduled' => $query->where('status', 'published')->where('published_at', '>', now()),
            'draft'     => $query->where('status', 'draft'),
            default     => null,
        };

        if (is_numeric($request->query('category'))) {
            $query->where('blog_category_id', (int) $request->query('category'));
        }

        $posts      = $query->paginate(15)->withQueryString();
        $categories = BlogCategory::orderBy('sort_order')->get(['id', 'name']);

        // The numbers on the tabs
        $counts = [
            'all'       => BlogPost::count(),
            'published' => BlogPost::published()->count(),
            'scheduled' => BlogPost::where('status', 'published')->where('published_at', '>', now())->count(),
            'draft'     => BlogPost::where('status', 'draft')->count(),
        ];

        return view('admin.posts.index', compact('posts', 'categories', 'counts', 'filter'));
    }

    // ---------- SEO AUDIT: every post scored, weakest first ----------
    public function audit()
    {
        // The 100 newest posts. The article text IS loaded here, because scoring needs it.
        $rows = BlogPost::select([
            'id',
            'title',
            'slug',
            'seo_title',
            'meta_description',
            'focus_keyword',
            'noindex',
            'featured_image',
            'featured_image_alt',
            'status',
            'published_at',
            'content'
        ])
            ->latest('id')
            ->take(100)
            ->get()
            ->map(function (BlogPost $post) {
                $seo = SeoScore::forPost($post);

                return ['post' => $post, 'seo' => $seo, 'level' => SeoScore::level($seo['score'])];
            })
            ->sortBy(fn($row) => $row['seo']['score'])
            ->values();

        $stats = [
            'total'   => $rows->count(),
            'average' => (int) round($rows->avg(fn($row) => $row['seo']['score']) ?? 0),
            'bad'     => $rows->where('level', 'bad')->count(),
            'good'    => $rows->where('level', 'good')->count(),
        ];

        return view('admin.posts.audit', compact('rows', 'stats'));
    }

    // ---------- WRITE A NEW POST ----------
    public function create()
    {
        $post = new BlogPost(['status' => 'draft']);

        return view('admin.posts.create', $this->formData($post));
    }

    public function store(PostRequest $request)
    {
        $data = $this->payload($request);
        $data['user_id'] = $request->user()->id;   // the author is always the person writing

        $post = BlogPost::create($data);
        $this->syncTags($post, $request->input('tags'));

        // If this address was an old one that redirected somewhere, the new article takes it over
        SlugRedirect::where('old_slug', $post->slug)->delete();
        $this->clearSitemap();

        return redirect()->route('admin.posts.edit', $post)
            ->with('success', '"' . $post->title . '" was saved.')
            ->with('alert_title', 'Post saved');
    }

    // ---------- EDIT ----------
    public function edit(BlogPost $post)
    {
        return view('admin.posts.edit', $this->formData($post));
    }

    public function update(PostRequest $request, BlogPost $post)
    {
        // Remember how the article stood BEFORE the change (needed for redirects)
        $oldSlug = $post->slug;
        $wasLive = $post->status === 'published' && $post->published_at && $post->published_at->isPast();

        $post->update($this->payload($request, $post));
        $this->syncTags($post, $request->input('tags'));
        $this->trackSlug($post, $oldSlug, $wasLive);
        $this->clearSitemap();

        return redirect()->route('admin.posts.edit', $post)
            ->with('success', '"' . $post->title . '" was updated.')
            ->with('alert_title', 'Post saved');
    }

    // ---------- DELETE (admin only) ----------
    public function destroy(Request $request, BlogPost $post)
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }
        $title = $post->title;
        $post->delete();   // its tag links, comments and redirects are removed automatically
        $this->clearSitemap();

        return redirect()->route('admin.posts.index')
            ->with('success', '"' . $title . '" was deleted.')
            ->with('alert_title', 'Post deleted');
    }

    // ---------- LIVE PREVIEW (the Preview tab) ----------
    public function preview(Request $request)
    {
        $data = $request->validate([
            'content' => ['nullable', 'string', 'max:60000'],
        ]);

        // The same safe renderer the public blog uses
        return response()->json(['html' => Markdown::toHtml($data['content'] ?? '')]);
    }

    // ---------- helpers ----------

    // The sitemap is kept for 10 minutes. Clear it, so a change shows up at once.
    private function clearSitemap(): void
    {
        Cache::forget('seo.sitemap');
    }

    // REDIRECTS. When a live article's address changes, the old address must keep working.
    private function trackSlug(BlogPost $post, string $oldSlug, bool $wasLive): void
    {
        // The article uses this address now, so no redirect may point away from it
        SlugRedirect::where('old_slug', $post->slug)->delete();

        // The redirect points to the ARTICLE (not to a fixed address), so after a second
        // rename the old addresses go straight to the newest one, with no chains.
        if ($wasLive && $oldSlug !== $post->slug) {
            SlugRedirect::updateOrCreate(['old_slug' => $oldSlug], ['blog_post_id' => $post->id]);
        }
    }

    private function formData(BlogPost $post): array
    {
        return [
            'post'       => $post,
            'categories' => BlogCategory::orderBy('sort_order')->get(['id', 'name']),
            'allTags'    => Tag::orderBy('name')->take(30)->pluck('name'),   // quick-add buttons
            'tagText'    => $post->exists ? $post->tags()->orderBy('name')->pluck('name')->implode(', ') : '',
        ];
    }

    // Turns the checked form values into what is saved in the database
    private function payload(PostRequest $request, ?BlogPost $post = null): array
    {
        // Everything validated, except the helper fields handled below
        $data = $request->safe()->except(['featured_image', 'remove_image', 'tags']);

        // An unticked checkbox is saved as false
        $data['noindex'] = $request->boolean('noindex');

        $data['focus_keyword'] = filled($data['focus_keyword'] ?? null)
            ? Str::squish($data['focus_keyword'])
            : null;

        // No slug typed? Build one from the title and make it unique
        $data['slug'] = filled($data['slug'] ?? null)
            ? $data['slug']
            : $this->uniqueSlug($data['title'], $post?->id);

        // DATE: the admin types Pakistan time. It is saved in UTC.
        $publishedAt = filled($data['published_at'] ?? null)
            ? Carbon::createFromFormat('Y-m-d\TH:i', $data['published_at'], config('shop.timezone'))->utc()
            : null;

        // Published with no date: keep the old date, or publish now
        if ($data['status'] === 'published' && ! $publishedAt) {
            $publishedAt = $post?->published_at ?? now();
        }
        $data['published_at'] = $publishedAt;

        // PHOTO: saved under a random safe name. The visitor's file name is never used.
        if ($request->hasFile('featured_image')) {
            if ($post?->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $data['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        } elseif ($post && $request->boolean('remove_image')) {
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $data['featured_image']     = null;
            $data['featured_image_alt'] = null;
        }

        return $data;
    }

    // "cozy, small space, cozy" becomes two tags. Tags that do not exist yet are created.
    private function syncTags(BlogPost $post, ?string $input): void
    {
        $ids = collect(explode(',', (string) $input))
            ->map(fn($name) => Str::squish($name))
            ->filter()
            ->unique(fn($name) => Str::lower($name))
            ->take(10)
            ->map(function ($name) {
                $slug = Str::slug($name);

                // A tag made only of symbols has no usable web address: skip it
                return $slug === '' ? null : Tag::firstOrCreate(['slug' => $slug], ['name' => $name])->id;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $post->tags()->sync($ids);
    }

    // "Cozy Bedroom" becomes cozy-bedroom; if taken, cozy-bedroom-2 ...
    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i    = 2;

        while (BlogPost::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
