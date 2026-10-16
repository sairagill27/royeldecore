@extends('layouts.app')

@section('title', $heading . ' | ' . config('app.name'))
@section('meta_description', $intro)

@push('head')
<link rel="canonical" href="{{ url()->current() }}">
@if ($noindex)
<meta name="robots" content="noindex, follow">
@endif
@endpush

@section('content')

{{-- ============ BANNER: title, search and category pills ============ --}}
<section class="sgh-shop-hero">
    <div class="container position-relative">

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

        <p class="sgh-eyebrow mb-2">The journal</p>
        <h1 class="sgh-shop-hero-title mb-2">{{ $heading }}</h1>
        <p class="text-secondary mb-3">{{ $intro }}</p>

        {{-- Search box --}}
        <form action="{{ route('blog.index') }}" method="GET" role="search" class="sgh-search-box mb-4">
            <label for="blog-q" class="visually-hidden">Search articles</label>
            <div class="input-group">
                {{-- {{ }} escapes what was typed (safe) --}}
                <input type="search" id="blog-q" name="q" value="{{ $term }}" maxlength="100"
                    class="form-control" placeholder="Search articles…">
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-search me-1"></i> Search</button>
            </div>
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

<div class="container py-4 py-lg-5">

    {{-- ============ FEATURED ARTICLE (the newest, blog home only) ============ --}}
    @if ($featured)
    <article class="sgh-featured-post mb-5">
        <div class="row g-0">
            <div class="col-lg-7">
                <a href="{{ route('blog.show', $featured->slug) }}" class="sgh-blog-media" aria-hidden="true" tabindex="-1">
                    @if ($featured->featured_image)
                    <img src="{{ asset('storage/' . $featured->featured_image) }}" alt="{{ $featured->featured_image_alt }}"
                        width="900" height="600" loading="eager" fetchpriority="high">
                    @else
                    <i class="bi bi-journal-richtext"></i>
                    @endif
                </a>
            </div>
            <div class="col-lg-5">
                <div class="sgh-featured-body">
                    <p class="sgh-eyebrow mb-2">Featured article</p>
                    @if ($featured->category)
                    <a href="{{ route('blog.category', $featured->category->slug) }}" class="sgh-blog-cat mb-2">{{ $featured->category->name }}</a>
                    @endif
                    <h2 class="sgh-featured-title">
                        <a href="{{ route('blog.show', $featured->slug) }}">{{ $featured->title }}</a>
                    </h2>
                    <p class="text-secondary">{{ $featured->summary(220) }}</p>
                    <p class="sgh-blog-meta mb-3">
                        {{ $featured->published_at->timezone(config('shop.timezone'))->format('d M Y') }}
                        @if ($featured->author) · {{ $featured->author->name }} @endif
                    </p>
                    <div>
                        <a href="{{ route('blog.show', $featured->slug) }}" class="btn btn-primary rounded-pill px-4">
                            Read article <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </article>
    @endif

    {{-- ============ ARTICLE GRID ============ --}}
    @if ($posts->isNotEmpty())

    @if ($featured)
    <h2 class="h4 mb-3">More articles</h2>
    @elseif ($term !== '')
    <p class="text-secondary small mb-3">{{ $posts->total() }} {{ \Illuminate\Support\Str::plural('result', $posts->total()) }} for "{{ $term }}"</p>
    @endif

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