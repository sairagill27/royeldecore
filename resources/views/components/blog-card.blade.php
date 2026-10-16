{{-- BLOG CARD: used on the blog home, category and tag pages, and the homepage.
     Usage: <x-blog-card :post="$post" /> --}}
@props(['post'])

<article class="card sgh-blog-card h-100">

    {{-- Photo (or a soft placeholder). The title link below is the real link, so this one is hidden from screen readers. --}}
    <a href="{{ route('blog.show', $post->slug) }}" class="sgh-blog-media" aria-hidden="true" tabindex="-1">
        @if ($post->featured_image)
        <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->featured_image_alt }}"
            width="640" height="400" loading="lazy" decoding="async">
        @else
        <i class="bi bi-journal-richtext"></i>
        @endif
    </a>

    <div class="card-body d-flex flex-column">
        @if ($post->category)
        <a href="{{ route('blog.category', $post->category->slug) }}" class="sgh-blog-cat mb-2">{{ $post->category->name }}</a>
        @endif

        {{-- {{ }} escapes the text, so nothing in a title can run as code --}}
        <h3 class="sgh-blog-title">
            <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
        </h3>

        <p class="sgh-blog-excerpt">{{ $post->summary(160) }}</p>

        <p class="sgh-blog-meta mt-auto mb-0">
            {{ $post->published_at->timezone(config('shop.timezone'))->format('d M Y') }}
        </p>
    </div>
</article>