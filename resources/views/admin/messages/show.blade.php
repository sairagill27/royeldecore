@extends('layouts.admin')

@section('title', 'Message')
@section('heading', 'Message')

@section('content')

<p class="mb-3">
    <a href="{{ route('admin.messages.index') }}"><i class="bi bi-arrow-left"></i> Back to messages</a>
</p>

<div class="row g-3">

    <div class="col-lg-8">
        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div>
                    {{-- {{ }} escapes the text, so nothing a visitor typed can run as code --}}
                    <h2>{{ $message->subject }}</h2>
                    <p>Received {{ $message->created_at->timezone(config('shop.timezone'))->format('d M Y, h:i A') }}</p>
                </div>
            </div>

            {{-- e() blocks any HTML; nl2br keeps the visitor's line breaks (safe) --}}
            <div class="sgh-message-body">{!! nl2br(e($message->message)) !!}</div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head">
                <div>
                    <h2>Sender</h2>
                </div>
            </div>
            <p class="mb-1 fw-semibold">{{ $message->name }}</p>
            <p class="small mb-3"><i class="bi bi-envelope text-secondary"></i> <a href="mailto:{{ $message->email }}">{{ $message->email }}</a></p>

            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: ' . $message->subject) }}"
                class="btn btn-primary w-100 rounded-pill mb-2">
                <i class="bi bi-reply me-1"></i> Reply by email
            </a>

            <form action="{{ route('admin.messages.unread', $message) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-secondary w-100 rounded-pill">Mark as unread</button>
            </form>
        </div>

        @if (auth()->user()->isAdmin())
        <div class="sgh-panel">
            <form action="{{ route('admin.messages.destroy', $message) }}" method="POST"
                onsubmit="return confirm('Delete this message? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger w-100 rounded-pill">Delete message</button>
            </form>
        </div>
        @endif
    </div>
</div>

@endsection