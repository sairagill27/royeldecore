{{-- WISHLIST HEART. Usage: <x-wishlist-button :product="$product" /> (a heart on a card)
     or <x-wishlist-button :product="$product" variant="text" /> (a labelled button).
     Logged-in customers save or remove. Visitors are sent to the login page. --}}
@props(['product', 'variant' => 'icon'])

@php
    $saved = \App\Models\WishlistItem::savedIds(auth()->id())->contains($product->id);
    $label = $saved ? 'Remove from wishlist' : 'Save to wishlist';
@endphp

@auth
    {{-- @csrf = security token --}}
    <form action="{{ route('wishlist.toggle', $product->id) }}" method="POST" class="sgh-wish sgh-wish--{{ $variant }}">
        @csrf
        <button type="submit" class="sgh-wish-btn {{ $saved ? 'is-saved' : '' }}"
                aria-label="{{ $label }}" aria-pressed="{{ $saved ? 'true' : 'false' }}" title="{{ $label }}">
            <i class="bi {{ $saved ? 'bi-heart-fill' : 'bi-heart' }}" aria-hidden="true"></i>
            @if ($variant === 'text')
                <span>{{ $saved ? 'Saved to wishlist' : 'Save to wishlist' }}</span>
            @endif
        </button>
    </form>
@else
    <div class="sgh-wish sgh-wish--{{ $variant }}">
        <a href="{{ route('login') }}" class="sgh-wish-btn" aria-label="Log in to save to your wishlist" title="Log in to save to your wishlist">
            <i class="bi bi-heart" aria-hidden="true"></i>
            @if ($variant === 'text')
                <span>Save to wishlist</span>
            @endif
        </a>
    </div>
@endauth