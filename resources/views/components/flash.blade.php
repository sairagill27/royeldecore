{{-- TOAST MESSAGES: shown after login, logout, add to cart, errors, etc.
     Any controller can trigger one with ->with('success', 'text')
     (also 'error', 'warning' or 'info'). --}}

@php
// Icon, title and how long (milliseconds) each type stays on screen
$types = [
'success' => ['icon' => 'bi-check-circle-fill', 'title' => 'Success', 'time' => 5000],
'error' => ['icon' => 'bi-x-circle-fill', 'title' => 'Something went wrong', 'time' => 8000],
'warning' => ['icon' => 'bi-exclamation-triangle-fill', 'title' => 'Please note', 'time' => 7000],
'info' => ['icon' => 'bi-info-circle-fill', 'title' => 'Notice', 'time' => 5000],
];

// Optional link inside a success toast, e.g. "View cart"
$action = session('toast_action');
@endphp

@if (session()->hasAny(array_keys($types)))
<div class="sgh-toasts">
    @foreach ($types as $type => $meta)
    @if (session($type))
    <div class="sgh-toast sgh-toast--{{ $type }}"
        role="{{ $type === 'error' ? 'alert' : 'status' }}"
        data-duration="{{ $meta['time'] }}"
        style="--sgh-toast-time: {{ $meta['time'] }}ms">

        <span class="sgh-toast-icon"><i class="bi {{ $meta['icon'] }}" aria-hidden="true"></i></span>

        <div class="sgh-toast-body">
            <strong>{{ $meta['title'] }}</strong>
            {{-- {{ }} escapes the text, so a message can never inject HTML --}}
            <p>{{ session($type) }}</p>

            @if ($type === 'success' && is_array($action))
            <a href="{{ $action['url'] }}" class="sgh-toast-action">
                {{ $action['label'] }} <i class="bi bi-arrow-right"></i>
            </a>
            @endif
        </div>

        <button type="button" class="sgh-toast-close" aria-label="Close message">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>

        {{-- Progress bar that shrinks while the toast is visible --}}
        <span class="sgh-toast-bar" aria-hidden="true"></span>
    </div>
    @endif
    @endforeach
</div>

<script>
    // Each toast closes itself after its time. Hovering pauses it.
    document.querySelectorAll('.sgh-toast').forEach(function(toast) {
        var remaining = parseInt(toast.dataset.duration, 10) || 5000;
        var started = Date.now();
        var timer;

        function close() {
            clearTimeout(timer);
            toast.classList.add('is-leaving');
            setTimeout(function() {
                toast.remove();
            }, 300);
        }

        function start() {
            started = Date.now();
            timer = setTimeout(close, remaining);
        }

        toast.addEventListener('mouseenter', function() {
            remaining -= Date.now() - started;
            clearTimeout(timer);
            toast.classList.add('is-paused');
        });
        toast.addEventListener('mouseleave', function() {
            toast.classList.remove('is-paused');
            start();
        });
        toast.querySelector('.sgh-toast-close').addEventListener('click', close);

        start();
    });
</script>
@endif