@extends('layouts.legal')

@section('title', 'Cookie Information | ' . config('app.name'))
@section('heading', 'Cookie Information')
@section('updated', '11 October 2026')

@section('body')

<div class="sgh-todo-note">
    <strong>Draft.</strong> This lists the cookies the site sets today. Update it when you add analytics or other tools,
    and ask a legal professional whether your country requires a cookie banner. Then delete this box.
</div>

<p>Cookies are small files a website saves in your browser. {{ config('app.name') }} uses only the cookies it needs to work.</p>

<h2>Cookies we use</h2>

<div class="table-responsive">
    <table class="table sgh-table align-middle">
        <thead>
            <tr>
                <th>Cookie</th>
                <th>What it does</th>
                <th>How long</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>{{ config('session.cookie') }}</code></td>
                <td>Keeps you logged in and remembers your shopping cart.</td>
                <td>About {{ config('session.lifetime') }} minutes after your last visit</td>
            </tr>
            <tr>
                <td><code>XSRF-TOKEN</code></td>
                <td>Protects forms (login, checkout, contact) from forged requests.</td>
                <td>Same as above</td>
            </tr>
            <tr>
                <td><code>remember_web_…</code></td>
                <td>Keeps you logged in on this device. Set only if you tick "Remember me".</td>
                <td>Long-lasting, until you log out</td>
            </tr>
        </tbody>
    </table>
</div>

<h2>Tracking and advertising</h2>
<p>We do not currently use advertising or tracking cookies. If we add website analytics later, we will explain it here.</p>

<h2>Other websites</h2>
<p>Our pages load fonts and code libraries from Google Fonts and jsDelivr. These providers can see your IP address when they deliver those files.</p>

<h2>Your choices</h2>
<p>You can delete or block cookies in your browser settings. If you block the cookies above, logging in, your cart and checkout will not work.</p>

<p>For more about how we handle your information, read our <a href="{{ url('/privacy-policy') }}">Privacy Policy</a>.</p>

@endsection