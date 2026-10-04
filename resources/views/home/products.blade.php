{{-- FEATURED PRODUCTS: data comes from HomeController ($featuredProducts) --}}
<section class="sgh-section" style="background: var(--sgh-soft-sage);">
    <div class="container">

        {{-- Heading with a "view all" link on the right --}}
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <p class="sgh-eyebrow mb-2">Featured products</p>
                <h2 class="sgh-section-title mb-0">Handpicked for your home</h2>
            </div>
            <a href="{{ url('/shop') }}" class="fw-semibold">View all products <i class="bi bi-arrow-right"></i></a>
        </div>

        @if ($featuredProducts->isEmpty())
        {{-- Shown if no product is marked featured --}}
        <p class="text-secondary">Featured products will appear here soon.</p>
        @else
        {{-- 1 per row on small phones, 2 on phones/tablets, 4 on large screens --}}
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 g-lg-4">
            @foreach ($featuredProducts as $product)
            <div class="col">
                <x-product-card :product="$product" />
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>