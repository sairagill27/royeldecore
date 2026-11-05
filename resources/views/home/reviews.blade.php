{{-- WHAT OUR CUSTOMERS SAY: the latest approved reviews from verified buyers ($homeReviews).
     Hidden until there is at least one. Everything is printed with {{ }}, so it is always plain text. --}}
@php
    // "Sara Khan" is shown as "Sara K." (never an email address)
    $shortName = function (string $name): string {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = $parts[0] ?? 'Customer';

        return count($parts) > 1 ? $first . ' ' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(end($parts), 0, 1)) . '.' : $first;
    };
@endphp

@if (($homeReviews ?? collect())->isNotEmpty())
<section class="sgh-section" style="background: var(--sgh-soft-sage);">
    <div class="container">

        <div class="text-center mb-5">
            <p class="sgh-eyebrow mb-2">Verified buyers</p>
            <h2 class="sgh-section-title">What our customers say</h2>
        </div>

        <div class="row row-cols-1 row-cols-md-3 g-3 g-lg-4">
            @foreach ($homeReviews as $review)
                <div class="col">
                    <figure class="sgh-home-review">
                        <span class="sgh-stars" role="img" aria-label="{{ $review->rating }} out of 5 stars">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                            @endfor
                        </span>
                        @if ($review->title)
                            <strong class="d-block mt-2">{{ $review->title }}</strong>
                        @endif
                        <blockquote>{{ \Illuminate\Support\Str::limit($review->body, 220) }}</blockquote>
                        <figcaption>
                            {{ $shortName($review->user?->name ?? 'Customer') }}
                            <span class="sgh-verified"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Verified buyer</span>
                            @if ($review->product)
                                <a href="{{ route('products.show', $review->product->slug) }}#reviews">{{ \Illuminate\Support\Str::limit($review->product->name, 36) }}</a>
                            @endif
                        </figcaption>
                    </figure>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif