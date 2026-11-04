@extends('layouts.app')

@php
    // Breadcrumb trail for search engines: Home > Blog > Category or Tag
    $crumbs = [['Home', url('/')], ['Blog', route('blog.index')]];
    if ($category) {
        $crumbs[] = [$category->name, route('blog.category', $category->slug)];
    } elseif ($tag) {
        $crumbs[] = [$tag->name, route('blog.tag', $tag->slug)];
    }

    $ld = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => collect($crumbs)->map(fn ($c, $i) => [
            '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1],
        ])->values()->all(),
    ];
@endphp

@section('title', $heading . ' | ' . config('app.name'))
@section('meta_description', $intro)

@push('head')
    <link rel="canonical" href="{{ $canonical }}">
    @if ($noindex)
        <meta name="robots" content="noindex, follow">
    @endif
    {!! \App\Services\JsonLd::tag($ld) !!}
@endpush

@section('content')

{{-- =====================================================
     HEADER: deep green, in the shop's own colours (same as the shop and the décor tools)
     ===================================================== --}}
<section class="sgh-tool-head sgh-shop-head sgh-blog-head">
    @include('tools._leaves')

    <div class="container position-relative sgh-blog-wrap">

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-3">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                @if ($category || $tag || $term !== '')
                    <li class="breadcrumb-item"><a href="{{ route('blog.index') }}">Blog</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $category->name ?? ($tag ? $tag->name : 'Search') }}</li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">Blog</li>
                @endif
            </ol>
        </nav>

        <span class="sgh-tool-badge"><i class="bi bi-journal-richtext" aria-hidden="true"></i> The journal</span>
        <h1 class="sgh-tool-head-title">{{ $heading }}</h1>
        <p class="sgh-tool-head-text">{{ $intro }}</p>

        {{-- Search box: a white pill. {{ }} escapes what was typed, so it is safe. --}}
        <form action="{{ route('blog.index') }}" method="GET" role="search" class="sgh-blog-search">
            <label for="blog-q" class="visually-hidden">Search articles</label>
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="blog-q" name="q" value="{{ $term }}" maxlength="100"
                   autocomplete="off" placeholder="Search articles&hellip;">
            <button type="submit" class="btn sgh-btn-dark rounded-pill px-4">Search</button>
        </form>

        {{-- Category pills: scroll sideways on phones --}}
        @if ($categories->isNotEmpty())
            <nav class="sgh-pills" aria-label="Blog categories">
                <a href="{{ route('blog.index') }}" class="sgh-pill {{ ! $category && ! $tag ? 'active' : '' }}">All</a>
                @foreach ($categories as $cat)
                    <a href="{{ route('blog.category', $cat->slug) }}"
                       class="sgh-pill {{ $category && $category->id === $cat->id ? 'active' : '' }}">{{ $cat->name }}</a>
                @endforeach
            </nav>
        @endif
    </div>
</section>


<div class="container py-4 py-lg-5 sgh-blog-wrap">

    {{-- ============ FEATURED ARTICLE (the newest, blog home only): a compact card ============ --}}
    @if ($featured)
        <article class="sgh-featured-post mb-5">
            <div class="row g-0">
                <div class="col-lg-5">
                    <a href="{{ route('blog.show', $featured->slug) }}" class="sgh-blog-media" aria-hidden="true" tabindex="-1">
                        @if ($featured->featured_image)
                            <img src="{{ asset('storage/' . $featured->featured_image) }}" alt="{{ $featured->featured_image_alt }}"
                                 width="900" height="600" loading="eager" fetchpriority="high">
                        @else
                            <i class="bi bi-journal-richtext"></i>
                        @endif
                    </a>
                </div>
                <div class="col-lg-7">
                    <div class="sgh-featured-body">
                        <span class="sgh-featured-flag"><i class="bi bi-star-fill" aria-hidden="true"></i> Featured article</span>

                        @if ($featured->category)
                            <a href="{{ route('blog.category', $featured->category->slug) }}" class="sgh-blog-cat mb-2">{{ $featured->category->name }}</a>
                        @endif

                        <h2 class="sgh-featured-title">
                            <a href="{{ route('blog.show', $featured->slug) }}">{{ $featured->title }}</a>
                        </h2>
                        <p class="sgh-featured-text">{{ $featured->summary(200) }}</p>

                        <p class="sgh-blog-meta mb-3">
                            {{ $featured->published_at->timezone(config('shop.timezone'))->format('d M Y') }}
                            @if ($featured->author) &middot; {{ $featured->author->name }} @endif
                        </p>
                        <div>
                            <a href="{{ route('blog.show', $featured->slug) }}" class="btn sgh-btn-dark rounded-pill px-4">
                                Read article <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    @endif

    {{-- ============ ARTICLE GRID ============ --}}
    @if ($posts->isNotEmpty())

        <div class="sgh-section-line">
            <h2>
                @if ($term !== '')
                    {{ $posts->total() }} {{ \Illuminate\Support\Str::plural('result', $posts->total()) }} for "{{ $term }}"
                @elseif ($featured)
                    Latest articles
                @else
                    Articles
                @endif
            </h2>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 g-lg-4">
            @foreach ($posts as $post)
                <div class="col">
                    <x-blog-card :post="$post" />
                </div>
            @endforeach
        </div>

        <div class="mt-5 d-flex justify-content-center">
            {{ $posts->links() }}
        </div>

    @elseif (! $featured)

        {{-- Nothing to show --}}
        <div class="sgh-empty text-center">
            <i class="bi bi-journal-text fs-1 text-secondary"></i>
            <h2 class="h5 mt-3">{{ $term !== '' ? 'No articles found' : 'No articles yet' }}</h2>
            <p class="text-secondary">
                {{ $term !== '' ? 'Check the spelling, or browse a category above.' : 'Fresh décor ideas are on their way. Please check back soon.' }}
            </p>
            <a href="{{ route('blog.index') }}" class="btn btn-primary rounded-pill px-4">All articles</a>
        </div>

    @endif
</div>

@endsection