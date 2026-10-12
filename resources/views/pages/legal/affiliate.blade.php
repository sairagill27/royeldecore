@extends('layouts.legal')

@section('title', 'Affiliate Disclosure | ' . config('app.name'))
@section('heading', 'Affiliate Disclosure')
@section('updated', '11 October 2026')

@section('body')

<div class="sgh-todo-note">
    <strong>Keep this page only if you use affiliate links.</strong> If you do not, delete this file, remove its line in
    <code>routes/web.php</code> and remove its link from the footer and the legal side menu.
    Then delete this box.
</div>

<p>Some links on {{ config('app.name') }} may be affiliate links. If you click one and buy something, we may earn a small commission from the seller. <strong>This costs you nothing extra.</strong></p>

<h2>Our promise</h2>
<p>We only recommend products and services we think are useful. A commission never changes the price you pay, and it does not decide what we write.</p>

<h2>Where you will see them</h2>
<p>Affiliate links may appear in articles, product suggestions and décor ideas. We aim to make them clear where they appear.</p>

<h2>Questions</h2>
<p>If you would like to know more, contact us at <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>

@endsection