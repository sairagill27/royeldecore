{{-- DECOR HELPER TEASER: promotes /helper with room shortcuts and a collage of your own product photos. --}}
@php
    $helperRooms = config('helper.rooms');
    $pics = collect($featuredProducts ?? [])->merge($newArrivals ?? [])
        ->filter(fn ($p) => filled($p->image))->unique('id')->take(4)->values();
@endphp

<section class="sgh-section sgh-helper">
    <div class="container">
        <div class="row align-items-center g-4 g-lg-5">

            <div class="col-lg-6">
                <p class="sgh-eyebrow mb-2">Free décor helper</p>
                <h2 class="sgh-section-title">Not sure where to start?</h2>
                <p class="text-secondary">Tell us the room and the style you like. We pick pieces from our shop and put together a mood board for you.</p>

                <p class="small text-secondary mb-2">Start with your room:</p>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    @foreach ($helperRooms as $key => $item)
                        <a href="{{ route('helper', ['room' => $key]) }}" class="btn btn-outline-primary btn-sm rounded-pill">
                            <i class="bi {{ $item['icon'] }} me-1" aria-hidden="true"></i>{{ $item['label'] }}
                        </a>
                    @endforeach
                </div>

                <a href="{{ route('helper') }}" class="btn sgh-btn-dark rounded-pill px-4">
                    Open the Décor Helper <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                </a>
                <a href="{{ route('assistant') }}" class="btn btn-outline-primary rounded-pill px-4 ms-1">
                    <i class="bi bi-chat-heart me-1" aria-hidden="true"></i>Ask the assistant
                </a>
            </div>

            <div class="col-lg-6">
                @if ($pics->count() >= 2)
                    <div class="row row-cols-2 g-2">
                        @foreach ($pics as $pic)
                            <div class="col">
                                <div class="ratio ratio-1x1 rounded-4 overflow-hidden shadow-sm">
                                    <img src="{{ asset('storage/' . $pic->image) }}" alt="" class="object-fit-cover"
                                         width="400" height="400" loading="lazy">
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="sgh-helper-card text-center">
                        <i class="bi bi-magic" aria-hidden="true"></i>
                        <p class="mb-0">Pick a room, a style and a budget, and see your mood board.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>