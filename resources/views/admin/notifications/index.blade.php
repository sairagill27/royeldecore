@extends('layouts.admin')

@section('title', 'Notifications')
@section('heading', 'Notifications')

@section('content')

@php
    $tz    = config('shop.timezone');
    $total = $messageCount + $commentCount + $reviewCount;
@endphp

{{-- ============ SUMMARY ============ --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg">
        <a href="{{ route('admin.messages.index') }}" class="text-decoration-none">
            <div class="sgh-stat sgh-stat--sage"><div><small>Unread messages</small><strong>{{ $messageCount }}</strong></div></div>
        </a>
    </div>
    <div class="col-6 col-lg">
        <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="text-decoration-none">
            <div class="sgh-stat sgh-stat--gold"><div><small>Comments waiting</small><strong>{{ $commentCount }}</strong></div></div>
        </a>
    </div>
    <div class="col-6 col-lg">
        <a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" class="text-decoration-none">
            <div class="sgh-stat sgh-stat--forest"><div><small>Reviews waiting</small><strong>{{ $reviewCount }}</strong></div></div>
        </a>
    </div>
    <div class="col-6 col-lg">
        <a href="{{ route('admin.orders.index') }}" class="text-decoration-none">
            <div class="sgh-stat sgh-stat--sage"><div><small>Orders, last 7 days</small><strong>{{ $orderCount }}</strong></div></div>
        </a>
    </div>
    <div class="col-12 col-lg">
        <a href="{{ route('admin.products.index') }}" class="text-decoration-none">
            <div class="sgh-stat sgh-stat--clay"><div><small>Low stock</small><strong>{{ $lowStock->count() }}</strong></div></div>
        </a>
    </div>
</div>

@if ($total === 0 && $orderCount === 0 && $lowStock->isEmpty())
    <div class="sgh-panel">
        <p class="sgh-panel-empty"><i class="bi bi-check2-circle"></i> You are all caught up. Nothing is waiting.</p>
    </div>
@endif

<div class="row g-3">

    {{-- MESSAGES --}}
    <div class="col-lg-6">
        <div class="sgh-panel h-100">
            <div class="sgh-panel-head">
                <div><h2>Unread messages</h2></div>
                <a href="{{ route('admin.messages.index') }}" class="small">View all</a>
            </div>
            @forelse ($messages as $message)
                <a href="{{ route('admin.messages.show', $message) }}" class="sgh-notice">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <span>
                        <strong>{{ $message->name ?? 'Visitor' }}</strong>
                        <small>{{ \Illuminate\Support\Str::limit((string) ($message->subject ?: $message->message), 60) }}</small>
                    </span>
                    <time>{{ $message->created_at->timezone($tz)->diffForHumans() }}</time>
                </a>
            @empty
                <p class="text-secondary small mb-0">No unread messages.</p>
            @endforelse
        </div>
    </div>

    {{-- COMMENTS --}}
    <div class="col-lg-6">
        <div class="sgh-panel h-100">
            <div class="sgh-panel-head">
                <div><h2>Comments waiting</h2></div>
                <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="small">View all</a>
            </div>
            @forelse ($comments as $comment)
                <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="sgh-notice">
                    <i class="bi bi-chat-dots" aria-hidden="true"></i>
                    <span>
                        <strong>{{ $comment->name }}</strong>
                        <small>{{ \Illuminate\Support\Str::limit($comment->body, 60) }}</small>
                    </span>
                    <time>{{ $comment->created_at->timezone($tz)->diffForHumans() }}</time>
                </a>
            @empty
                <p class="text-secondary small mb-0">No comments waiting.</p>
            @endforelse
        </div>
    </div>

    {{-- REVIEWS --}}
    <div class="col-lg-6">
        <div class="sgh-panel h-100">
            <div class="sgh-panel-head">
                <div><h2>Reviews waiting</h2></div>
                <a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" class="small">View all</a>
            </div>
            @forelse ($reviews as $review)
                <a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" class="sgh-notice">
                    <i class="bi bi-star" aria-hidden="true"></i>
                    <span>
                        <strong>{{ $review->rating }} / 5 &middot; {{ $review->product?->name ?? 'Product' }}</strong>
                        <small>{{ \Illuminate\Support\Str::limit($review->body, 60) }}</small>
                    </span>
                    <time>{{ $review->created_at->timezone($tz)->diffForHumans() }}</time>
                </a>
            @empty
                <p class="text-secondary small mb-0">No reviews waiting.</p>
            @endforelse
        </div>
    </div>

    {{-- ORDERS --}}
    <div class="col-lg-6">
        <div class="sgh-panel h-100">
            <div class="sgh-panel-head">
                <div><h2>New orders (7 days)</h2></div>
                <a href="{{ route('admin.orders.index') }}" class="small">View all</a>
            </div>
            @forelse ($orders as $order)
                <a href="{{ route('admin.orders.show', $order->order_number) }}" class="sgh-notice">
                    <i class="bi bi-receipt" aria-hidden="true"></i>
                    <span>
                        <strong>{{ $order->order_number }}</strong>
                        <small>{{ ucfirst((string) $order->status) }}</small>
                    </span>
                    <time>{{ $order->created_at->timezone($tz)->diffForHumans() }}</time>
                </a>
            @empty
                <p class="text-secondary small mb-0">No orders in the last 7 days.</p>
            @endforelse
        </div>
    </div>

    {{-- LOW STOCK --}}
    <div class="col-12">
        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div><h2>Low stock</h2><p>Products with {{ $threshold }} or fewer left.</p></div>
                <a href="{{ route('admin.products.index') }}" class="small">All products</a>
            </div>
            @forelse ($lowStock as $product)
                <a href="{{ route('admin.products.edit', $product) }}" class="sgh-notice">
                    <i class="bi bi-box-seam" aria-hidden="true"></i>
                    <span><strong>{{ $product->name }}</strong></span>
                    <time>{{ $product->stock }} left</time>
                </a>
            @empty
                <p class="text-secondary small mb-0">Every product has enough stock.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection