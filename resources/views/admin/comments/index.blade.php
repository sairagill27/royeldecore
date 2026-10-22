@extends('layouts.admin')

@section('title', 'Comments')
@section('heading', 'Comments')

@section('content')

@php $tz = config('shop.timezone'); @endphp

<div class="sgh-panel">

    {{-- Status tabs, with counters --}}
    <nav class="sgh-pills mb-3" aria-label="Filter comments by status">
        <a href="{{ route('admin.comments.index') }}" class="sgh-pill {{ $filter ? '' : 'active' }}">All ({{ $counts->sum() }})</a>
        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
        <a href="{{ route('admin.comments.index', ['status' => $key]) }}"
            class="sgh-pill {{ $filter === $key ? 'active' : '' }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
        @endforeach
    </nav>

    @if ($comments->isEmpty())

    <p class="sgh-panel-empty"><i class="bi bi-chat-dots"></i> No comments here.</p>

    @else
    <div class="table-responsive">
        <table class="table sgh-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Comment</th>
                    <th>Article</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($comments as $comment)
                <tr>
                    <td class="sgh-comment-cell">
                        {{-- Everything is escaped, so a comment can never run as code here --}}
                        <strong>{{ $comment->name }}</strong>
                        <small class="text-secondary">{{ $comment->email }}</small>
                        <div class="mt-1">{!! nl2br(e($comment->body)) !!}</div>
                    </td>
                    <td>
                        @if ($comment->post)
                        <a href="{{ route('blog.show', $comment->post->slug) }}#comments" target="_blank" rel="noopener" class="text-decoration-none">{{ \Illuminate\Support\Str::limit($comment->post->title, 40) }}</a>
                        @else
                        —
                        @endif
                    </td>
                    <td class="text-nowrap">{{ $comment->created_at->timezone($tz)->format('d M Y, h:i A') }}</td>
                    <td>
                        @if ($comment->status === 'approved')
                        <span class="badge sgh-badge-ok">Approved</span>
                        @elseif ($comment->status === 'rejected')
                        <span class="badge sgh-badge-out">Rejected</span>
                        @else
                        <span class="badge sgh-badge-low">Pending</span>
                        @endif
                    </td>
                    <td>
                        <div class="sgh-row-actions">
                            @if ($comment->status !== 'approved')
                            <form action="{{ route('admin.comments.approve', $comment) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="sgh-icon-action" title="Approve" aria-label="Approve comment by {{ $comment->name }}"><i class="bi bi-check-lg"></i></button>
                            </form>
                            @endif

                            @if ($comment->status !== 'rejected')
                            <form action="{{ route('admin.comments.reject', $comment) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="sgh-icon-action" title="Reject" aria-label="Reject comment by {{ $comment->name }}"><i class="bi bi-x-lg"></i></button>
                            </form>
                            @endif

                            {{-- Delete: admins only --}}
                            @if (auth()->user()->isAdmin())
                            <form action="{{ route('admin.comments.destroy', $comment) }}" method="POST"
                                onsubmit="return confirm('Delete this comment? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="sgh-icon-action is-danger" title="Delete" aria-label="Delete comment by {{ $comment->name }}"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $comments->links() }}</div>
    @endif
</div>

@endsection