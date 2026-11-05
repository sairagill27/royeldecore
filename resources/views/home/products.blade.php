{{-- FEATURED PRODUCTS: up to 4 ($featuredProducts comes from HomeController) --}}
<section class="sgh-section" style="background: var(--sgh-soft-sage);">
    <div class="container">

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <p class="sgh-eyebrow mb-2">Featured products</p>
                <h2 class="sgh-section-title mb-0">Handpicked for your home</h2>
            </div>
            <a href="{{ url('/shop') }}" class="fw-semibold">View all products <i class="bi bi-arrow-right"></i></a>
        </div>

        @if ($featuredProducts->isNotEmpty())
            <div class="row row-cols-2 row-cols-lg-4 g-2 g-md-3 g-lg-4">
                @foreach ($featuredProducts as $product)
                    <div class="col"><x-product-card :product="$product" /></div>
                @endforeach
            </div>
        @else
            <p class="text-secondary">Featured products will appear here soon.</p>
        @endif
    </div>
</section>