{{-- CUSTOMER REVIEWS under a product. Needs $product. Only approved reviews are shown.
     Everything typed by customers is printed with {{ }}, so it is always plain text. --}}
@php
    $stats    = \App\Models\ProductReview::approved()->where('product_id', $product->id)
                    ->selectRaw('count(*) as total, avg(rating) as average')->first();
    $total    = (int) ($stats->total ?? 0);
    $average  = $total > 0 ? round((float) $stats->average, 1) : 0;
    $reviews  = \App\Models\ProductReview::approved()->where('product_id', $product->id)
                    ->with('user:id,name')->latest('id')->take(30)->get();

    $me       = auth()->user();
    $mine     = $me ? \App\Models\ProductReview::where('product_id', $product->id)->where('user_id', $me->id)->first() : null;
    $canWrite = $me && ! $mine && \App\Models\ProductReview::canBeWrittenBy($me, $product->id);

    // "Sara Khan" is shown as "Sara K." (never an email address)
    $shortName = function (string $name): string {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = $parts[0] ?? 'Customer';

        return count($parts) > 1 ? $first . ' ' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(end($parts), 0, 1)) . '.' : $first;
    };
@endphp

<section class="sgh-section pt-0" id="reviews">
    <div class="container">
        <div class="sgh-reviews">

            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                <h2 class="h4 mb-0">Customer reviews ({{ $total }})</h2>
                @if ($total > 0)
                    <span class="sgh-stars" role="img" aria-label="Average rating {{ $average }} out of 5">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= round($average) ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                        @endfor
                        <strong class="ms-1">{{ $average }}</strong><small class="text-secondary"> / 5</small>
                    </span>
                @endif
            </div>

            @forelse ($reviews as $review)
                <div class="sgh-review">
                    <div class="sgh-review-head">
                        <span class="sgh-stars" role="img" aria-label="{{ $review->rating }} out of 5 stars">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                            @endfor
                        </span>
                        @if ($review->title)
                            <strong>{{ $review->title }}</strong>
                        @endif
                    </div>
                    <p class="mb-1">{!! nl2br(e($review->body)) !!}</p>
                    <p class="sgh-review-meta mb-0">
                        {{ $shortName($review->user?->name ?? 'Customer') }}
                        <span class="sgh-verified"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Verified buyer</span>
                        · {{ $review->created_at->timezone(config('shop.timezone'))->format('d M Y') }}
                    </p>
                </div>
            @empty
                <p class="text-secondary">There are no reviews yet.</p>
            @endforelse

            {{-- ============ WRITE A REVIEW ============ --}}
            <div class="sgh-comment-form mt-4">
                @if ($canWrite)
                    <h3 class="h5 mb-3">Write a review</h3>

                    {{-- @csrf = security token --}}
                    <form action="{{ route('products.reviews.store', $product->slug) }}" method="POST" novalidate>
                        @csrf

                        {{-- SPAM TRAP: people never see this field. If it is filled in, a bot did it. --}}
                        <div class="sgh-hp" aria-hidden="true">
                            <label for="rv-website">Leave this field empty</label>
                            <input type="text" id="rv-website" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <fieldset class="mb-3">
                            <legend class="form-label">Your rating <span class="text-danger" aria-hidden="true">*</span></legend>
                            <div class="btn-group flex-wrap" role="group" aria-label="Star rating">
                                @foreach ([1, 2, 3, 4, 5] as $n)
                                    <input type="radio" class="btn-check" name="rating" id="rv-star-{{ $n }}" value="{{ $n }}"
                                           autocomplete="off" @checked((string) old('rating') === (string) $n)>
                                    <label class="btn btn-outline-primary btn-sm px-3" for="rv-star-{{ $n }}">{{ $n }} <i class="bi bi-star-fill" aria-hidden="true"></i></label>
                                @endforeach
                            </div>
                            @error('rating') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </fieldset>

                        <div class="mb-3">
                            <label for="rv-title" class="form-label">Title <span class="text-secondary">(optional)</span></label>
                            <input type="text" id="rv-title" name="title" value="{{ old('title') }}" maxlength="100"
                                   class="form-control @error('title') is-invalid @enderror">
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="rv-body" class="form-label">Your review <span class="text-danger" aria-hidden="true">*</span></label>
                            <textarea id="rv-body" name="body" rows="4" maxlength="1500"
                                      class="form-control @error('body') is-invalid @enderror" required>{{ old('body') }}</textarea>
                            @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Reviews are checked before they appear. Please be honest and kind, and include at most one link.</div>
                        </div>

                        <button type="submit" class="btn btn-primary rounded-pill px-4">Submit review</button>
                    </form>

                @elseif ($mine)
                    <p class="mb-0">
                        <i class="bi bi-check-circle me-1"></i>
                        @if ($mine->status === 'pending')
                            Thank you for your review. It will appear after it has been approved.
                        @elseif ($mine->status === 'approved')
                            Thank you. Your review is shown above.
                        @else
                            Your review was not published.
                        @endif
                    </p>

                @elseif ($me)
                    <p class="mb-0 text-secondary">Only customers who have received this product can review it.</p>

                @else
                    <p class="mb-0 text-secondary">
                        <a href="{{ route('login') }}">Log in</a> to write a review. Only customers who have received this product can review it.
                    </p>
                @endif
            </div>
        </div>
    </div>
</section>