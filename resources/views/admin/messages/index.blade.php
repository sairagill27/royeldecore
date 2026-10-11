@extends('layouts.admin')

@section('title', 'Messages')
@section('heading', 'Messages')

@section('content')

<div class="sgh-panel">

    {{-- Tabs: All / Unread --}}
    <nav class="sgh-pills mb-3" aria-label="Filter messages">
        <a href="{{ route('admin.messages.index') }}" class="sgh-pill {{ $filter === 'all' ? 'active' : '' }}">All ({{ $totalCount }})</a>
        <a href="{{ route('admin.messages.index', ['filter' => 'unread']) }}" class="sgh-pill {{ $filter === 'unread' ? 'active' : '' }}">Unread ({{ $unreadCount }})</a>
    </nav>

    @if ($messages->isEmpty())

    <p class="sgh-panel-empty">
        <i class="bi bi-envelope-open"></i>
        {{ $filter === 'unread' ? 'No unread messages.' : 'No messages yet. Messages from your Contact page appear here.' }}
    </p>

    @else
    <div class="table-responsive">
        <table class="table sgh-table align-middle mb-0">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Received</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($messages as $item)
                {{-- Unread messages are shown in bold, with a green dot --}}
                <tr class="{{ $item->isUnread() ? 'sgh-unread' : '' }}">
                    <td>
                        @if ($item->isUnread()) <span class="sgh-dot" title="Unread"></span> @endif
                        {{ $item->name }}
                        <small class="d-block text-secondary fw-normal">{{ $item->email }}</small>
                    </td>
                    <td>
                        <a href="{{ route('admin.messages.show', $item) }}" class="text-decoration-none text-reset">
                            {{ $item->subject }}
                        </a>
                        <small class="d-block text-secondary fw-normal">{{ \Illuminate\Support\Str::limit($item->message, 70) }}</small>
                    </td>
                    <td class="text-nowrap">{{ $item->created_at->timezone(config('shop.timezone'))->format('d M Y, h:i A') }}</td>
                    <td>
                        <div class="sgh-row-actions">
                            <a href="{{ route('admin.messages.show', $item) }}" class="sgh-icon-action"
                                title="Open" aria-label="Open message from {{ $item->name }}"><i class="bi bi-eye"></i></a>

                            @if (auth()->user()->isAdmin())
                            <form action="{{ route('admin.messages.destroy', $item) }}" method="POST"
                                onsubmit="return confirm('Delete this message? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sgh-icon-action is-danger"
                                    title="Delete" aria-label="Delete message from {{ $item->name }}"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $messages->links() }}</div>
    @endif
</div>

@endsection