{{-- HOW ORDERING WORKS: three steps that match what checkout really does (cash on delivery only). --}}
@php
    $currency = config('shop.currency', 'Rs.');
    $fee      = (int) config('shop.shipping_fee', 0);
    $over     = (int) config('shop.free_shipping_over', 0);
@endphp

<section class="sgh-section">
    <div class="container">

        <div class="text-center mb-5">
            <p class="sgh-eyebrow mb-2">Simple and clear</p>
            <h2 class="sgh-section-title">How ordering works</h2>
        </div>

        <div class="row row-cols-1 row-cols-md-3 g-3 g-lg-4">
            <div class="col">
                <div class="sgh-flow-card">
                    <span class="sgh-flow-num">1</span>
                    <span class="sgh-flow-icon"><i class="bi bi-bag-heart" aria-hidden="true"></i></span>
                    <h3>Choose your pieces</h3>
                    <p>Browse the shop, save favourites with the heart, and add what you like to your cart. Stock is always up to date.</p>
                </div>
            </div>
            <div class="col">
                <div class="sgh-flow-card">
                    <span class="sgh-flow-num">2</span>
                    <span class="sgh-flow-icon"><i class="bi bi-cash-coin" aria-hidden="true"></i></span>
                    <h3>Place your order</h3>
                    <p>Create a free account and enter your delivery details. You pay by cash on delivery, when your order arrives.</p>
                </div>
            </div>
            <div class="col">
                <div class="sgh-flow-card">
                    <span class="sgh-flow-num">3</span>
                    <span class="sgh-flow-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                    <h3>Follow your order</h3>
                    <p>You get an order confirmation, and you can see every status change in My Orders.</p>
                </div>
            </div>
        </div>

        @if ($fee > 0)
            <p class="sgh-flow-note">
                <i class="bi bi-truck" aria-hidden="true"></i>
                Delivery charge: {{ $currency }} {{ number_format($fee) }}@if ($over > 0), and no charge on orders over {{ $currency }} {{ number_format($over) }}@endif.
            </p>
        @endif
    </div>
</section>