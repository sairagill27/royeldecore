{{-- RELATED PICKS under a blog article: up to 3 active links that an admin tied to this article's blog category.
     Links already placed inside the article with a shortcode are left out. Nothing shows when there are none. --}}
@props(['post'])

@php
    $picks = collect();

    if ($post->blog_category_id) {
        $content = (string) $post->content;

        $picks = \App\Models\AffiliateLink::active()
            ->where('blog_category_id', $post->blog_category_id)
            ->orderByDesc('is_featured')
            ->latest('id')
            ->take(12)
            ->get()
            ->filter(fn ($link) => \App\Models\AffiliateLink::safeUrl($link->url) && ! str_contains($content, '[[affiliate:' . $link->slug . ']]'))
            ->take(3)
            ->values();
    }
@endphp

@if ($picks->isNotEmpty())
    <section class="sgh-section pt-0" aria-labelledby="related-picks-title">
        <div class="container">
            <div class="mx-auto" style="max-width: 1000px;">
                <h2 class="h4 mb-3" id="related-picks-title">Related picks</h2>
                <x-affiliate-disclosure />
                <div class="row g-4">
                    @foreach ($picks as $pick)
                        <div class="col-12 col-md-6 col-lg-4">
                            <x-affiliate-card :link="$pick" variant="tile" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif