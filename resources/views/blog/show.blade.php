@extends('layouts.app')

@php
$url = route('blog.show', $post->slug);
$shareUrl = rawurlencode($url);
$shareText = rawurlencode($post->title);
$date = $post->published_at->timezone(config('shop.timezone'));
$image = $post->featured_image ? asset('storage/' . $post->featured_image) : null;
$metaDesc = $post->meta_description ?: $post->summary(155);
$ogTitle = $post->seo_title ?: $post->title;
$updated = $post->updated_at->gt($post->published_at->copy()->addDay());

// Structured data for search engines (printed safely by JsonLd::tag below)
$articleLd = [
'@type' => 'BlogPosting',
'headline' => $post->title,
'description' => $metaDesc,
'datePublished' => $post->published_at->toIso8601String(),
'dateModified' => $post->updated_at->toIso8601String(),
'author' => ['@type' => 'Person', 'name' => $post->author?->name ?? config('app.name')],
'publisher' => ['@type' => 'Organization', 'name' => config('app.name'),
'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/images/logo.png')]],
'mainEntityOfPage' => $url,
'inLanguage' => 'en',
'wordCount' => \Illuminate\Support\Str::wordCount(strip_tags($post->content)),
];
if ($image) {
$articleLd['image'] = [$image];
}
if ($post->category) {
$articleLd['articleSection'] = $post->category->name;
}
if ($post->tags->isNotEmpty()) {
$articleLd['keywords'] = $post->tags->pluck('name')->implode(', ');
}

// Breadcrumb trail: Home > Blog > Category > Article
$crumbs = [['Home', url('/')], ['Blog', route('blog.index')]];
if ($post->category) {
$crumbs[] = [$post->category->name, route('blog.category', $post->category->slug)];
}
$crumbs[] = [$post->title, $url];

$breadcrumbLd = [
'@type' => 'BreadcrumbList',
'itemListElement' => collect($crumbs)->map(fn ($c, $i) => [
'@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1],
])->values()->all(),
];

$ld = ['@context' => 'https://schema.org', '@graph' => [$articleLd, $breadcrumbLd]];
@endphp

@section('title', $ogTitle . ' | ' . config('app.name'))
@section('meta_description', $metaDesc)
@section('has_og', '1')

@push('head')
<link rel="canonical" href="{{ $url }}">

{{-- Authors can switch an article out of search engines in the editor --}}
@if ($post->noindex)
<meta name="robots" content="noindex, follow">
@endif

{{-- Shown when the article is shared (WhatsApp, Facebook, X...) --}}
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
<meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
@if ($post->category)
<meta property="article:section" content="{{ $post->category->name }}">
@endif
@foreach ($post->tags as $tag)
<meta property="article:tag" content="{{ $tag->name }}">
@endforeach
@if ($image)
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:alt" content="{{ $post->featured_image_alt }}">
@endif
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $metaDesc }}">
@if ($image)
<meta name="twitter:image" content="{{ $image }}">
@endif

{!! \App\Services\JsonLd::tag($ld) !!}
@endpush
@section('content')

{{-- Shown only when the page is opened from a private preview link --}}
@if (! empty($preview))
    <div class="sgh-preview-bar" role="status">
        <i class="bi bi-eye" aria-hidden="true"></i>
        @if (! empty($previewNotLive))
            <strong>Preview:</strong> this article is not published yet, so visitors cannot see it.
        @else
            <strong>Preview:</strong> this is your latest saved version of a live article.
        @endif
        This private link stops working after 24 hours.
    </div>
@endif

{{-- Reading progress: a thin line along the top of the screen --}}
<div class="sgh-read-progress" aria-hidden="true"><span id="readBar"></span></div>

<article>
    {{-- ============ ARTICLE HEADER ============ --}}
    <header class="sgh-tool-head sgh-article-hero">
    @include('tools._leaves')
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

            {{-- The one and only H1 of the page. {{ }} escapes it, so nothing in it can run as code. --}}
            <h1 class="sgh-article-title mt-2 mb-3">{{ $post->title }}</h1>

            @if (filled($post->excerpt))
            <p class="lead text-secondary mb-3">{{ $post->excerpt }}</p>
            @endif

            <p class="sgh-blog-meta mb-0">
                @if ($post->author) By <strong>{{ $post->author->name }}</strong> · @endif
                <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $date->format('d F Y') }}</time>
                @if ($updated)
                · Updated <time datetime="{{ $post->updated_at->toIso8601String() }}">{{ $post->updated_at->timezone(config('shop.timezone'))->format('d F Y') }}</time>
                @endif
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

        <div class="sgh-article-body">

            {{-- ============ CONTENTS BOX (only for articles with 3 or more headings) ============ --}}
            @if (count($toc) >= 3)
            <nav class="sgh-toc" aria-label="In this article">
                <strong>In this article</strong>
                <ol>
                    @foreach ($toc as $item)
                    <li class="{{ $item['level'] === 3 ? 'is-sub' : '' }}"><a href="#{{ $item['id'] }}">{{ $item['text'] }}</a></li>
                    @endforeach
                </ol>
            </nav>
            @endif

            {{-- ============ THE ARTICLE TEXT ============
                 $html comes from the safe Markdown renderer: raw HTML is already removed. --}}
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
@include('blog.comments', ['post' => $post])

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