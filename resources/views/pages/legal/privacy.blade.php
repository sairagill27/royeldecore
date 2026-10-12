@extends('layouts.legal')

@section('title', 'Privacy Policy | ' . config('app.name'))
@section('heading', 'Privacy Policy')
@section('updated', '11 October 2026')

@section('body')

<div class="sgh-todo-note">
    <strong>Draft for you to complete.</strong> Parts in <mark class="sgh-todo">[yellow]</mark> need your real
    information. Have this page reviewed by a legal professional before launch, then delete this box.
    If you later add a newsletter, analytics or online payments, update this page too.
</div>

<p>This policy explains what personal information {{ config('app.name') }} ("we", "us") collects, why we collect it, and the choices you have.</p>

<h2>Who we are</h2>
<p>
    {{ config('app.name') }} is an online home décor shop. You can reach us at
    <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a> or through our
    <a href="{{ route('contact') }}">contact page</a>.
    Business name and address: <mark class="sgh-todo">[your registered business name and address]</mark>.
</p>

<h2>What we collect</h2>
<ul>
    <li><strong>Account details:</strong> your name, email address and password. Your password is stored only as a scrambled (hashed) value, so we cannot read it.</li>
    <li><strong>Order details:</strong> your name, email, phone number, delivery address, the items you ordered, and any note you add.</li>
    <li><strong>Messages:</strong> your name, email, subject and message when you use our contact form.</li>
    <li><strong>Technical data:</strong> small files called cookies (see our <a href="{{ url('/cookies') }}">Cookie Information</a>). Our web server may also record standard technical logs, such as your IP address and browser type.</li>
</ul>

<h2>Why we use it</h2>
<ul>
    <li>To process, deliver and support your orders.</li>
    <li>To run your account and keep it secure, for example by limiting repeated login attempts.</li>
    <li>To reply to your messages.</li>
    <li>To keep business, tax and accounting records.</li>
</ul>

<h2>Who we share it with</h2>
<p>We do not sell your personal information. We share only what is needed with:</p>
<ul>
    <li>Delivery partners, to deliver your order: <mark class="sgh-todo">[name your courier companies]</mark>.</li>
    <li>Our email and hosting providers, who help us send emails and keep the website running: <mark class="sgh-todo">[name your providers]</mark>.</li>
    <li>Authorities, if the law requires it.</li>
</ul>
<p>Our pages also load fonts and code libraries from Google Fonts and jsDelivr. These providers can see your IP address when they deliver those files.</p>

<h2>How long we keep it</h2>
<p>We keep order records for as long as needed for business, tax and legal reasons: <mark class="sgh-todo">[for example, 5 years. Check your local requirements]</mark>. Contact messages are kept <mark class="sgh-todo">[for example, 12 months]</mark> and then deleted.</p>

<h2>How we protect it</h2>
<p>Passwords are hashed, forms are protected against forged requests, and only authorised staff can open the admin area. No website is completely risk-free, but we work to keep your information safe.</p>

<h2>Your choices</h2>
<p>You can ask to see, correct or delete the personal information we hold about you, subject to records we must keep by law. You can update your name, email and password yourself in <a href="{{ url('/account/profile') }}">your account</a>. For anything else, contact us.</p>

<h2>Children</h2>
<p>Our shop is not aimed at children under <mark class="sgh-todo">[18]</mark>, and we do not knowingly collect their information.</p>

<h2>Changes to this policy</h2>
<p>If we change this policy, we will update the date at the top of this page.</p>

<h2>Contact</h2>
<p>Questions about this policy? Write to <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>

@endsection