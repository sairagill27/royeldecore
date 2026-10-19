<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    // /sitemap.xml: a list of every page search engines should find.
    // Kept for 10 minutes, so busy crawlers do not hit the database every time.
    public function sitemap()
    {
        $xml = Cache::remember('seo.sitemap', 600, fn() => $this->buildSitemap());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    // /robots.txt: tells search engines where they may go
    public function robots()
    {
        // Only the real, live site may be indexed. Anywhere else (your PC, a test server)
        // blocks everyone, so a test copy can never show up in Google.
        if (! app()->isProduction()) {
            $body = "User-agent: *\nDisallow: /\n";
        } else {
            $body = "User-agent: *\n"
                . "Disallow: /admin\n"
                . "Disallow: /cart\n"
                . "Disallow: /checkout\n"
                . "Disallow: /account\n"
                . "Disallow: /login\n"
                . "Disallow: /register\n"
                . "Disallow: /forgot-password\n"
                . "Disallow: /reset-password\n"
                . "Disallow: /newsletter\n"
                . "Disallow: /search\n"
                // Searching and filtering make endless near-copies of the same page
                . "Disallow: /*?q=\n"
                . "Disallow: /*?sort=\n"
                . "Disallow: /*?min=\n"
                . "Disallow: /*?max=\n"
                . "Disallow: /*?in_stock=\n"
                . "Allow: /\n\n"
                . 'Sitemap: ' . route('sitemap') . "\n";
        }

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    // /blog/feed.xml: an RSS feed of the 20 newest articles
    public function feed()
    {
        $posts = BlogPost::published()->indexable()->forCards()
            ->latest('published_at')
            ->take(20)
            ->get();

        $items = '';
        foreach ($posts as $post) {
            $link = route('blog.show', $post->slug);

            $items .= "    <item>\n"
                . '      <title>' . $this->x($post->title) . "</title>\n"
                . '      <link>' . $this->x($link) . "</link>\n"
                . '      <guid isPermaLink="true">' . $this->x($link) . "</guid>\n"
                . '      <pubDate>' . $post->published_at->toRfc2822String() . "</pubDate>\n"
                . ($post->category ? '      <category>' . $this->x($post->category->name) . "</category>\n" : '')
                . '      <description>' . $this->x($post->summary(300)) . "</description>\n"
                . "    </item>\n";
        }

        $built = ($posts->first()?->published_at ?? now())->toRfc2822String();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n"
            . "  <channel>\n"
            . '    <title>' . $this->x(config('app.name') . ' blog') . "</title>\n"
            . '    <link>' . $this->x(route('blog.index')) . "</link>\n"
            . '    <description>' . $this->x('Home décor ideas and inspiration from ' . config('app.name')) . "</description>\n"
            . "    <language>en</language>\n"
            . '    <lastBuildDate>' . $built . "</lastBuildDate>\n"
            . '    <atom:link href="' . $this->x(route('blog.feed')) . '" rel="self" type="application/rss+xml"/>' . "\n"
            . $items
            . "  </channel>\n</rss>\n";

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    // ---------- helpers ----------

    private function buildSitemap(): string
    {
        $urls = [];
        $add  = function (string $loc, $lastmod = null) use (&$urls) {
            $urls[] = ['loc' => $loc, 'lastmod' => $lastmod ? Carbon::parse($lastmod)->toAtomString() : null];
        };

        // Main pages
        $add(route('home'));
        $add(route('shop'));
        $add(route('blog.index'), BlogPost::published()->indexable()->max('updated_at'));
        $add(route('about'));
        $add(route('contact'));
        $add(route('legal.privacy'));
        $add(route('legal.terms'));
        $add(route('legal.disclaimer'));
        $add(route('legal.cookies'));

        // Shop: visible categories and products (hidden ones are left out)
        foreach (Category::where('is_active', true)->get(['slug', 'updated_at']) as $category) {
            $add(route('shop', ['category' => $category->slug]), $category->updated_at);
        }
        foreach (Product::visible()->select(['slug', 'updated_at'])->cursor() as $product) {
            $add(route('products.show', $product->slug), $product->updated_at);
        }

        // Blog: live articles that are allowed in search engines, and categories and tags that have articles
        foreach (BlogPost::published()->indexable()->select(['slug', 'updated_at'])->cursor() as $post) {
            $add(route('blog.show', $post->slug), $post->updated_at);
        }
        foreach (BlogCategory::whereHas('posts', fn($q) => $q->published())->get(['slug', 'updated_at']) as $category) {
            $add(route('blog.category', $category->slug), $category->updated_at);
        }
        foreach (Tag::whereHas('posts', fn($q) => $q->published())->get(['slug', 'updated_at']) as $tag) {
            $add(route('blog.tag', $tag->slug), $tag->updated_at);
        }

        // A sitemap may hold up to 50,000 addresses
        $urls = array_slice($urls, 0, 50000);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $u) {
            $xml .= "  <url>\n    <loc>" . $this->x($u['loc']) . "</loc>\n";
            if ($u['lastmod']) {
                $xml .= '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
            }
            $xml .= "  </url>\n";
        }

        return $xml . '</urlset>' . "\n";
    }

    // Makes text safe to put inside XML: strips characters XML does not allow, then escapes & < > " '
    private function x(?string $text): string
    {
        $text = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string) $text);

        return htmlspecialchars((string) $text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
