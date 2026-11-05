{{-- QUESTIONS? A band with a WhatsApp and/or email button. It only appears when a valid contact is set
     in .env (CONTACT_WHATSAPP or CONTACT_EMAIL), so it never shows a broken or empty button. --}}
@php
    $email = config('contact.email');
    $email = (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : null;

    $wa = preg_replace('/\D/', '', (string) config('contact.whatsapp'));
    $wa = (strlen($wa) >= 10 && strlen($wa) <= 15) ? $wa : null;

    $hello = rawurlencode('Hello ' . config('app.name') . ', I have a question.');
@endphp

@if ($wa || $email)
<section class="sgh-section pt-0">
    <div class="container">
        <div class="sgh-home-contact">
            <div>
                <h2>Questions about a piece?</h2>
                <p>Message us and we will help you choose. You can also use the contact form.</p>
            </div>
            <div class="sgh-home-contact-actions">
                @if ($wa)
                    <a href="https://wa.me/{{ $wa }}?text={{ $hello }}" target="_blank" rel="noopener noreferrer" class="btn sgh-btn-dark rounded-pill px-4">
                        <i class="bi bi-whatsapp me-1" aria-hidden="true"></i> Chat on WhatsApp
                    </a>
                @endif
                @if ($email)
                    <a href="mailto:{{ $email }}" class="btn btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-envelope me-1" aria-hidden="true"></i> Email us
                    </a>
                @endif
                <a href="{{ route('contact') }}" class="btn btn-link">Contact form</a>
            </div>
        </div>
    </div>
</section>
@endif