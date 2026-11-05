{{-- NEW ARRIVALS: your newest products that are not already featured. Hidden when there are none. --}}
@if (($newArrivals ?? collect())->isNotEmpty())
<section class="sgh-section">
    <div class="container">

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <p class="sgh-eyebrow mb-2">Just in</p>
                <h2 class="sgh-section-title mb-0">New arrivals</h2>
            </div>
            <a href="{{ url('/shop') }}" class="fw-semibold">Shop everything <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="row row-cols-2 row-cols-lg-4 g-2 g-md-3 g-lg-4">
            @foreach ($newArrivals as $product)
                <div class="col"><x-product-card :product="$product" /></div>
            @endforeach
        </div>
    </div>
</section>
@endif