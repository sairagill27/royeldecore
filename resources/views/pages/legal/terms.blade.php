@extends('layouts.legal')

@section('title', 'Terms & Conditions | ' . config('app.name'))
@section('heading', 'Terms & Conditions')
@section('updated', '11 October 2026')

@section('body')

@php
// These come from config/shop.php, so the page always matches your real checkout
$cur = config('shop.currency');
$fee = (float) config('shop.shipping_fee');
$free = (float) config('shop.free_shipping_over');
@endphp

<div class="sgh-todo-note">
    <strong>Draft for you to complete.</strong> Parts in <mark class="sgh-todo">[yellow]</mark> need your real
    decisions, especially the returns section. Have this page reviewed by a legal professional before launch,
    then delete this box.
</div>

<p>By using this website or placing an order, you agree to these terms. Please read them carefully.</p>

<h2>Using the website</h2>
<p>Please use the website lawfully and give us correct information. You are responsible for keeping your account password private.</p>

<h2>Products and prices</h2>
<p>
    Prices are shown in {{ $cur }}. We try to keep descriptions, photos and prices accurate. Colours may look slightly
    different on your screen, and sizes may vary a little. If we find a mistake in a price, we may correct it
    and contact you before going ahead with your order.
</p>

<h2>Orders</h2>
<p>
    Placing an order is your offer to buy. We confirm it, usually by phone or email. We may decline or cancel an order,
    for example if an item is out of stock, a price was wrong, or we cannot reach you to confirm.
</p>

<h2>Payment</h2>
<p>Payment is <strong>cash on delivery</strong>: you pay the delivery person when your order arrives. We may add other payment methods later and will update this page when we do.</p>

<h2>Delivery</h2>
<p>
    Delivery costs {{ $cur }} {{ number_format($fee) }}@if ($free > 0), and is free for orders of {{ $cur }} {{ number_format($free) }} or more @endif.
    The exact amount is shown at checkout before you place your order.
    Delivery times: <mark class="sgh-todo">[for example, 3 to 7 working days]</mark>.
    Please make sure your address and phone number are correct.
</p>

<h2>Cancellations</h2>
<p>You can ask us to cancel an order <strong>before it has been shipped</strong> by contacting us with your order number. Once an order has shipped, it can no longer be cancelled, but our returns section below may apply.</p>

<h2>Returns and refunds</h2>
<p>
    <mark class="sgh-todo">[Decide your policy and write it here. For example: items that arrive damaged or are not what you ordered
        can be reported within 3 days of delivery, with photos, and we will replace the item or refund you. State which items cannot be
        returned, such as personalized décor, and who pays for return delivery.]</mark>
</p>

<h2>Our content</h2>
<p>The text, photos, logo and design on this website belong to {{ config('app.name') }} or its suppliers. Please do not copy or reuse them without our permission.</p>

<h2>Our responsibility</h2>
<p>
    We take care to keep the website and our products to a good standard, but we cannot promise the website will always be available
    or free of errors. To the extent the law allows, we are not responsible for indirect losses.
    Nothing in these terms limits any rights you have under the law.
</p>

<h2>Governing law</h2>
<p>These terms are governed by the laws of <mark class="sgh-todo">[your country or region]</mark>.</p>

<h2>Changes</h2>
<p>We may update these terms. The date at the top shows when they last changed. The terms that applied when you placed an order apply to that order.</p>

<h2>Contact</h2>
<p>Questions? Write to <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a> or use our <a href="{{ route('contact') }}">contact page</a>.</p>

@endsection