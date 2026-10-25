@extends('layouts.admin')

@section('title', 'SEO audit')
@section('heading', 'SEO audit')

@section('content')

<p class="mb-3">
    <a href="{{ route('admin.posts.index') }}"><i class="bi bi-arrow-left"></i> Back to posts</a>
</p>

{{-- ============ SUMMARY ============ --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="sgh-stat sgh-stat--sage">
            <div><small>Posts checked</small><strong>{{ $stats['total'] }}</strong></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sgh-stat sgh-stat--forest">
            <div><small>Average score</small><strong>{{ $stats['average'] }}</strong></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sgh-stat sgh-stat--clay">
            <div><small>Need work</small><strong>{{ $stats['bad'] }}</strong></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sgh-stat sgh-stat--gold">
            <div><small>Looking good</small><strong>{{ $stats['good'] }}</strong></div>
        </div>
    </div>
</div>

<div class="sgh-panel">
    <div class="sgh-panel-head">
        <div>
            <h2>Posts, weakest first</h2>
            <p>The score is a writing guide, not a ranking promise. The 100 newest posts are checked.</p>
        </div>
    </div>

    @if ($rows->isEmpty())

    <p class="sgh-panel-empty"><i class="bi bi-journal-text"></i> No posts yet. Write one and it will be scored here.</p>

    @else
    <div class="table-responsive">
        <table class="table sgh-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Post</th>
                    <th class="text-center">Score</th>
                    <th>What to fix first</th>
                    <th class="text-end"><span class="visually-hidden">Edit</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                @php
                $post = $row['post'];
                $failed = collect($row['seo']['checks'])->where('ok', false)->take(2);
                @endphp
                <tr>
                    <td>
                        {{-- {{ }} escapes the text (safe) --}}
                        <a href="{{ route('admin.posts.edit', $post) }}" class="fw-semibold text-decoration-none">{{ $post->title }}</a>
                        <small class="d-block text-secondary">
                            {{ $post->focus_keyword ? 'Keyword: ' . $post->focus_keyword : 'No focus keyword' }}
                            · {{ $row['seo']['words'] }} words
                            @if ($post->status === 'draft') · Draft @endif
                            @if ($post->noindex) · noindex @endif
                        </small>
                    </td>
                    <td class="text-center">
                        <span class="sgh-score sgh-score--{{ $row['level'] }}">{{ $row['seo']['score'] }}</span>
                    </td>
                    <td class="small">
                        @forelse ($failed as $check)
                        <span class="d-block"><i class="bi bi-x-circle-fill text-danger me-1"></i>{{ $check['label'] }}</span>
                        @empty
                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>All checks passed</span>
                        @endforelse
                    </td>
                    <td>
                        <div class="sgh-row-actions">
                            <a href="{{ route('admin.posts.edit', $post) }}" class="sgh-icon-action"
                                title="Edit" aria-label="Edit {{ $post->title }}"><i class="bi bi-pencil"></i></a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection