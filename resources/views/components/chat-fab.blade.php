{{-- A floating button to the assistant. Not shown on the assistant page, the cart or checkout. --}}
@unless (request()->routeIs('assistant', 'cart.index', 'checkout'))
    <a href="{{ route('assistant') }}" class="sgh-chat-fab btn sgh-btn-dark rounded-pill shadow px-3 py-2"
       aria-label="Ask our décor assistant">
        <i class="bi bi-chat-heart" aria-hidden="true"></i><span class="d-none d-sm-inline ms-2">Ask us</span>
    </a>
@endunless