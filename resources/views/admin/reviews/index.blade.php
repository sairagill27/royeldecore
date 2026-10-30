@extends('layouts.admin')

@section('title', 'Reviews')
@section('heading', 'Reviews')

@section('content')

@php $tz = config('shop.timezone'); @endphp

<div class="sgh-panel">

    {{-- Status tabs, with counters --}}
    <nav class="sgh-pills mb-3" aria-label="Filter reviews by status">
        <a href="{{ route('admin.reviews.index') }}" class="sgh-pill {{ $filter ? '' : 'active' }}">All ({{ $counts->sum() }})</a>
        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
            <a href="{{ route('admin.reviews.index', ['status' => $key]) }}"
               class="sgh-pill {{ $filter === $key ? 'active' : '' }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
        @endforeach
    </nav>

    @if ($reviews->isEmpty())

        <p class="sgh-panel-empty"><i class="bi bi-star"></i> No reviews here.</p>

    @else
        <div class="table-responsive">
            <table class="table sgh-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Review</th>
                        <th>Product</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reviews as $review)
                        <tr>
                            <td class="sgh-comment-cell">
                                {{-- Everything is escaped, so a review can never run as code here --}}
                                <span class="sgh-stars" role="img" aria-label="{{ $review->rating }} out of 5 stars">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                                    @endfor
                                </span>
                                @if ($review->title) <strong class="ms-1">{{ $review->title }}</strong> @endif
                                <div class="small text-secondary">{{ $review->user?->name ?? 'Deleted customer' }} · {{ $review->user?->email }}</div>
                                <div class="mt-1">{!! nl2br(e($review->body)) !!}</div>
                            </td>
                            <td>
                                @if ($review->product)
                                    <a href="{{ route('products.show', $review->product->slug) }}#reviews" target="_blank" rel="noopener" class="text-decoration-none">{{ \Illuminate\Support\Str::limit($review->product->name, 36) }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $review->created_at->timezone($tz)->format('d M Y, h:i A') }}</td>
                            <td>
                                @if ($review->status === 'approved')
                                    <span class="badge sgh-badge-ok">Approved</span>
                                @elseif ($review->status === 'rejected')
                                    <span class="badge sgh-badge-out">Rejected</span>
                                @else
                                    <span class="badge sgh-badge-low">Pending</span>
                                @endif
                            </td>
                            <td>
                                <div class="sgh-row-actions">
                                    @if ($review->status !== 'approved')
                                        <form action="{{ route('admin.reviews.approve', $review) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="sgh-icon-action" title="Approve" aria-label="Approve review"><i class="bi bi-check-lg"></i></button>
                                        </form>
                                    @endif

                                    @if ($review->status !== 'rejected')
                                        <form action="{{ route('admin.reviews.reject', $review) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="sgh-icon-action" title="Reject" aria-label="Reject review"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    @endif

                                    {{-- Delete: admins only --}}
                                    @if (auth()->user()->isAdmin())
                                        <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST"
                                              onsubmit="return confirm('Delete this review? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="sgh-icon-action is-danger" title="Delete" aria-label="Delete review"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">{{ $reviews->links() }}</div>
    @endif
</div>

@endsection