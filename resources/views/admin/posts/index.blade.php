@extends('layouts.admin')

@section('title', 'Blog posts')
@section('heading', 'Blog posts')

@section('content')

@php $tz = config('shop.timezone'); @endphp

<div class="sgh-panel">

    {{-- Status tabs, with counters --}}
    <nav class="sgh-pills mb-3" aria-label="Filter posts by status">
        <a href="{{ route('admin.posts.index', array_filter(['q' => request('q')])) }}"
            class="sgh-pill {{ $filter ? '' : 'active' }}">All ({{ $counts['all'] }})</a>
        @foreach (['published' => 'Published', 'scheduled' => 'Scheduled', 'draft' => 'Draft'] as $key => $label)
        <a href="{{ route('admin.posts.index', array_filter(['status' => $key, 'q' => request('q')])) }}"
            class="sgh-pill {{ $filter === $key ? 'active' : '' }}">{{ $label }} ({{ $counts[$key] }})</a>
        @endforeach
    </nav>

    <div class="sgh-admin-toolbar">
        <form action="{{ route('admin.posts.index') }}" method="GET" class="sgh-admin-filters" role="search">
            @if ($filter) <input type="hidden" name="status" value="{{ $filter }}"> @endif

            <label for="q" class="visually-hidden">Search posts</label>
            <input type="search" id="q" name="q" value="{{ request('q') }}"
                class="form-control form-control-sm" placeholder="Search titles…">

            <label for="category" class="visually-hidden">Category</label>
            <select name="category" id="category" class="form-select form-select-sm">
                <option value="">All categories</option>
                @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected((string) request('category')===(string) $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Filter</button>
            @if (request('q') || request('category') || $filter)
            <a href="{{ route('admin.posts.index') }}" class="btn btn-link btn-sm">Reset</a>
            @endif
        </form>

        <div class="d-flex gap-2 ms-lg-auto">
            <a href="{{ route('admin.posts.audit') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-graph-up-arrow me-1"></i> SEO audit
            </a>
            <a href="{{ route('admin.posts.create') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                <i class="bi bi-plus-lg me-1"></i> Write a post
            </a>
        </div>
    </div>

    @if ($posts->isEmpty())

    <p class="sgh-panel-empty"><i class="bi bi-journal-text"></i> No posts found. Click "Write a post" to start.</p>

    @else
    <div class="table-responsive">
        <table class="table sgh-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($posts as $post)
                @php $scheduled = $post->status === 'published' && $post->published_at && $post->published_at->isFuture(); @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <span class="sgh-admin-thumb">
                                @if ($post->featured_image)
                                <img src="{{ asset('storage/' . $post->featured_image) }}" alt="" width="44" height="44" loading="lazy">
                                @else
                                <i class="bi bi-image" aria-hidden="true"></i>
                                @endif
                            </span>
                            <div>
                                {{-- {{ }} escapes the text (safe) --}}
                                <a href="{{ route('admin.posts.edit', $post) }}" class="fw-semibold text-decoration-none">{{ $post->title }}</a>
                                <small class="d-block text-secondary">/blog/{{ $post->slug }}</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ $post->category?->name ?? '—' }}</td>
                    <td>{{ $post->author?->name ?? '—' }}</td>
                    <td class="text-nowrap">
                        {{ $post->published_at ? $post->published_at->copy()->timezone($tz)->format('d M Y, h:i A') : '—' }}
                    </td>
                    <td>
                        @if ($post->status === 'draft')
                        <span class="badge sgh-badge-draft">Draft</span>
                        @elseif ($scheduled)
                        <span class="badge sgh-badge-low">Scheduled</span>
                        @else
                        <span class="badge sgh-badge-ok">Published</span>
                        @endif

                        @if ($post->noindex)
                        <span class="badge sgh-badge-draft" title="Hidden from search engines">noindex</span>
                        @endif
                    </td>
                    <td>
                        <div class="sgh-row-actions">
                            <a href="{{ route('admin.posts.edit', $post) }}" class="sgh-icon-action"
                                title="Edit" aria-label="Edit {{ $post->title }}"><i class="bi bi-pencil"></i></a>

                            {{-- Delete: admins only --}}
                            @if (auth()->user()->isAdmin())
                            <form action="{{ route('admin.posts.destroy', $post) }}" method="POST"
                                onsubmit="return confirm('Delete this post? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sgh-icon-action is-danger"
                                    title="Delete" aria-label="Delete {{ $post->title }}"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $posts->links() }}</div>
    @endif
</div>

@endsection