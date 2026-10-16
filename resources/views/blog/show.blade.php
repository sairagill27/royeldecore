@extends('layouts.app')

@php
$url = route('blog.show', $post->slug);
$shareUrl = rawurlencode($url);
$shareText = rawurlencode($post->title);
$date = $post->published_at->timezone(config('shop.timezone'));
$image = $post->featured_image ? asset('storage/' . $post->featured_image) : null;
$metaDesc = $post->meta_description ?: $post->summary(155);

// Structured data for search engines. JSON_HEX_TAG and friends make sure text such as
// </script> inside a title can never end the tag early.
$ld = [
'@context' => 'https://schema.org',
'@type' => 'BlogPosting',
'headline' => $post->title,
'datePublished' => $post->published_at->toIso8601String(),
'dateModified' => $post->updated_at->toIso8601String(),
'author' => ['@type' => 'Person', 'name' => $post->author?->name ?? config('app.name')],
'publisher' => ['@type' => 'Organization', 'name' => config('app.name')],
'mainEntityOfPage' => $url,
];
if ($image) {
$ld['image'] = $image;
}
@endphp

@section('title', ($post->seo_title ?: $post->title) . ' | ' . config('app.name'))
@section('meta_description', $metaDesc)

@push('head')
<link rel="canonical" href="{{ $url }}">

{{-- Shown when the article is shared (WhatsApp, Facebook...) --}}
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $post->seo_title ?: $post->title }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
@if ($image)
<meta property="og:image" content="{{ $image }}">
@endif
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">

<script type="application/ld+json">
    {
        !!json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!
    }
</script>
@endpush

@section('content')

{{-- Reading progress: a thin line along the top of the screen --}}
<div class="sgh-read-progress" aria-hidden="true"><span id="readBar"></span></div>

<article>
    {{-- ============ ARTICLE HEADER ============ --}}
    <header class="sgh-shop-hero">
        <div class="container position-relative sgh-article-head">

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-3 justify-content-center">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('blog.index') }}">Blog</a></li>
                    @if ($post->category)
                    <li class="breadcrumb-item"><a href="{{ route('blog.category', $post->category->slug) }}">{{ $post->category->name }}</a></li>
                    @endif
                </ol>
            </nav>

            @if ($post->category)
            <a href="{{ route('blog.category', $post->category->slug) }}" class="sgh-blog-cat">{{ $post->category->name }}</a>
            @endif

            {{-- {{ }} escapes the title, so nothing in it can run as code --}}
            <h1 class="sgh-article-title mt-2 mb-3">{{ $post->title }}</h1>

            @if (filled($post->excerpt))
            <p class="lead text-secondary mb-3">{{ $post->excerpt }}</p>
            @endif

            <p class="sgh-blog-meta mb-0">
                @if ($post->author) By <strong>{{ $post->author->name }}</strong> · @endif
                <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $date->format('d F Y') }}</time>
                · {{ $post->readingMinutes() }} min read
            </p>
        </div>
    </header>

    <div class="container pb-5">

        {{-- ============ COVER PHOTO ============ --}}
        @if ($post->featured_image)
        <figure class="sgh-article-cover">
            <img src="{{ $image }}" alt="{{ $post->featured_image_alt }}" width="1200" height="675"
                loading="eager" fetchpriority="high">
        </figure>
        @endif

        {{-- ============ THE ARTICLE TEXT ============
             $html comes from the safe Markdown renderer: raw HTML is already removed. --}}
        <div class="sgh-article-body">
            <div class="sgh-prose" id="articleText">{!! $html !!}</div>

            {{-- Tags --}}
            @if ($post->tags->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mt-4">
                @foreach ($post->tags as $tag)
                <a href="{{ route('blog.tag', $tag->slug) }}" class="sgh-chip">#{{ $tag->name }}</a>
                @endforeach
            </div>
            @endif

            {{-- Share: plain links, no third-party scripts --}}
            <div class="sgh-share">
                <strong class="me-1">Share</strong>
                <a href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp"><i class="bi bi-whatsapp"></i></a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook"><i class="bi bi-facebook"></i></a>
                <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener noreferrer" aria-label="Share on X"><i class="bi bi-twitter-x"></i></a>
                <button type="button" id="copyLink" data-url="{{ $url }}" aria-label="Copy link"><i class="bi bi-link-45deg"></i> <span>Copy link</span></button>
            </div>

            {{-- Author box --}}
            @if ($post->author)
            <div class="sgh-author">
                <span class="sgh-author-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($post->author->name, 0, 1)) }}</span>
                <div>
                    <strong class="d-block">{{ $post->author->name }}</strong>
                    <span class="small text-secondary">Writes about home décor at {{ config('app.name') }}.</span>
                </div>
            </div>
            @endif
        </div>
    </div>
</article>

{{-- ============ FROM OUR SHOP ============ --}}
@if ($products->isNotEmpty())
<section class="sgh-section pt-0">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <h2 class="h4 mb-0">From our shop</h2>
            <a href="{{ url('/shop') }}" class="fw-semibold">Shop all décor <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row row-cols-2 row-cols-lg-4 g-2 g-md-3">
            @foreach ($products as $product)
            <div class="col"><x-product-card :product="$product" /></div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ KEEP READING ============ --}}
@if ($related->isNotEmpty())
<section class="sgh-section pt-0">
    <div class="container">
        <h2 class="h4 mb-4">Keep reading</h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 g-lg-4">
            @foreach ($related as $item)
            <div class="col"><x-blog-card :post="$item" /></div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Reading progress line, and the Copy link button --}}
<script>
    (function() {
        var bar = document.getElementById('readBar');
        var text = document.getElementById('articleText');

        function progress() {
            var rect = text.getBoundingClientRect();
            var total = rect.height - window.innerHeight * 0.6;
            var done = Math.min(1, Math.max(0, (-rect.top + window.innerHeight * 0.2) / Math.max(total, 1)));
            bar.style.width = (done * 100) + '%';
        }
        window.addEventListener('scroll', progress, {
            passive: true
        });
        window.addEventListener('resize', progress);
        progress();

        var copy = document.getElementById('copyLink');
        copy.addEventListener('click', function() {
            var label = copy.querySelector('span');

            function done() {
                label.textContent = 'Copied!';
                setTimeout(function() {
                    label.textContent = 'Copy link';
                }, 2000);
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(copy.dataset.url).then(done);
            } else {
                window.prompt('Copy this link:', copy.dataset.url); // older browsers
            }
        });
    })();
</script>

@endsection