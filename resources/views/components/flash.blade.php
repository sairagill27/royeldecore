{{-- MESSAGES: centered alert popup, or a small corner toast (for add-to-cart).
     Any controller can trigger one with ->with('success', 'text')
     (also 'error', 'warning' or 'info'). Optional: ->with('alert_title', 'Login successful') --}}

@php
// Order matters: if several messages exist, the first one in this list is shown
$types = [
'error' => ['icon' => 'bi-x-lg', 'toast_icon' => 'bi-x-circle-fill', 'title' => 'Something went wrong', 'alert_time' => 0, 'toast_time' => 8000],
'warning' => ['icon' => 'bi-exclamation-lg', 'toast_icon' => 'bi-exclamation-triangle-fill', 'title' => 'Please note', 'alert_time' => 0, 'toast_time' => 7000],
'success' => ['icon' => 'bi-check-lg', 'toast_icon' => 'bi-check-circle-fill', 'title' => 'Success', 'alert_time' => 5000, 'toast_time' => 5000],
'info' => ['icon' => 'bi-info-lg', 'toast_icon' => 'bi-info-circle-fill', 'title' => 'Notice', 'alert_time' => 5000, 'toast_time' => 5000],
];

// Which message do we have? (null = nothing to show)
$type = collect(array_keys($types))->first(fn ($t) => session($t));
$meta = $type ? $types[$type] : null;
$action = session('toast_action'); // optional link, e.g. "View cart"
$asToast = $type && is_array($action); // messages with a link stay small corner toasts
@endphp

@if ($type && $asToast)

{{-- ============ SMALL CORNER TOAST (add to cart) ============ --}}
<div class="sgh-toasts">
    <div class="sgh-toast sgh-toast--{{ $type }}"
        role="{{ $type === 'error' ? 'alert' : 'status' }}"
        data-duration="{{ $meta['toast_time'] }}"
        style="--sgh-toast-time: {{ $meta['toast_time'] }}ms">

        <span class="sgh-toast-icon"><i class="bi {{ $meta['toast_icon'] }}" aria-hidden="true"></i></span>

        <div class="sgh-toast-body">
            <strong>{{ session('alert_title') ?? $meta['title'] }}</strong>
            {{-- {{ }} escapes the text, so a message can never inject HTML --}}
            <p>{{ session($type) }}</p>
            <a href="{{ $action['url'] }}" class="sgh-toast-action">
                {{ $action['label'] }} <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <button type="button" class="sgh-toast-close" aria-label="Close message">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
        <span class="sgh-toast-bar" aria-hidden="true"></span>
    </div>
</div>

<script>
    // The toast closes itself after its time. Hovering pauses it.
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

@elseif ($type)

{{-- ============ CENTERED ALERT POPUP ============
         <dialog> is built into the browser: it dims the page, traps the keyboard
         focus inside, and closes with the Esc key. --}}
<dialog class="sgh-alert sgh-alert--{{ $type }}" id="sghAlert"
    aria-labelledby="sghAlertTitle" aria-describedby="sghAlertText"
    data-duration="{{ $meta['alert_time'] }}"
    style="--sgh-alert-time: {{ $meta['alert_time'] }}ms">

    <div class="sgh-alert-icon"><i class="bi {{ $meta['icon'] }}" aria-hidden="true"></i></div>

    <h2 id="sghAlertTitle">{{ session('alert_title') ?? $meta['title'] }}</h2>
    {{-- {{ }} escapes the text, so a message can never inject HTML --}}
    <p id="sghAlertText">{{ session($type) }}</p>

    <button type="button" class="btn btn-primary rounded-pill px-5" data-alert-close>OK</button>

    {{-- Timer bar: only for messages that close by themselves --}}
    @if ($meta['alert_time'] > 0)
    <span class="sgh-alert-bar" aria-hidden="true"></span>
    @endif
</dialog>

<script>
    (function() {
        var dlg = document.getElementById('sghAlert');
        if (!dlg) return;

        // Open as a modal (dimmed background). Very old browsers just show it.
        if (typeof dlg.showModal === 'function') {
            dlg.showModal();
        } else {
            dlg.setAttribute('open', '');
        }

        var remaining = parseInt(dlg.dataset.duration, 10) || 0;
        var started = Date.now();
        var timer;

        function close() {
            clearTimeout(timer);
            dlg.classList.add('is-leaving');
            setTimeout(function() {
                if (typeof dlg.close === 'function') {
                    dlg.close();
                } else {
                    dlg.removeAttribute('open');
                }
            }, 200);
        }

        function start() {
            if (remaining <= 0) return; // errors and warnings do not close by themselves
            started = Date.now();
            timer = setTimeout(close, remaining);
        }

        // Hovering pauses the timer
        dlg.addEventListener('mouseenter', function() {
            if (remaining <= 0) return;
            remaining -= Date.now() - started;
            clearTimeout(timer);
            dlg.classList.add('is-paused');
        });
        dlg.addEventListener('mouseleave', function() {
            if (parseInt(dlg.dataset.duration, 10) <= 0) return;
            dlg.classList.remove('is-paused');
            start();
        });

        dlg.querySelector('[data-alert-close]').addEventListener('click', close);

        // Clicking the dimmed area outside the box closes it
        dlg.addEventListener('click', function(e) {
            var r = dlg.getBoundingClientRect();
            if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) {
                close();
            }
        });

        start();
    })();
</script>

@endif