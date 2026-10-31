{{-- Public contact details as list items. A detail that is empty (or not valid) is not shown.
     Put it inside a <ul>. Everything is printed with {{ }}, so it is escaped. --}}
@php
    $email = config('contact.email');
    $email = (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : null;

    $phone       = trim((string) config('contact.phone'));
    $phoneDigits = preg_replace('/[^0-9+]/', '', $phone);

    $wa      = preg_replace('/\D/', '', (string) config('contact.whatsapp'));
    $address = trim((string) config('contact.address'));
    $hours   = trim((string) config('contact.hours'));
@endphp

@if ($email)
    <li class="sgh-contact-item"><i class="bi bi-envelope" aria-hidden="true"></i> <a href="mailto:{{ $email }}">{{ $email }}</a></li>
@endif
@if ($phone !== '' && strlen($phoneDigits) >= 7)
    <li class="sgh-contact-item"><i class="bi bi-telephone" aria-hidden="true"></i> <a href="tel:{{ $phoneDigits }}">{{ $phone }}</a></li>
@endif
@if (strlen($wa) >= 10 && strlen($wa) <= 15)
    <li class="sgh-contact-item"><i class="bi bi-whatsapp" aria-hidden="true"></i> <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener noreferrer">WhatsApp us</a></li>
@endif
@if ($address !== '')
    <li class="sgh-contact-item"><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $address }}</li>
@endif
@if ($hours !== '')
    <li class="sgh-contact-item"><i class="bi bi-clock" aria-hidden="true"></i> {{ $hours }}</li>
@endif