@extends('layouts.legal')

@section('title', 'Disclaimer | ' . config('app.name'))
@section('heading', 'Disclaimer')
@section('updated', '11 October 2026')

@section('body')

<div class="sgh-todo-note">
    <strong>Draft.</strong> Have this page reviewed before launch, then delete this box.
</div>

<h2>Information only</h2>
<p>The décor ideas, articles and tips on {{ config('app.name') }} are for general inspiration. They are not professional advice. Your home, budget and taste are your own, so please use your own judgement.</p>

<h2>Colours, sizes and photos</h2>
<p>Product colours can look different on different screens and in different light. Sizes and finishes may vary slightly from the photos and descriptions.</p>

<h2>Calculators and estimates</h2>
<p>Our décor tools, such as the paint, flooring, wallpaper and curtain calculators, give <strong>estimates only</strong>. Real needs depend on your room, the material and how it is installed. Please check measurements and ask your supplier or installer before you buy materials.</p>

<h2>Safe installation</h2>
<p>If you hang heavy items such as mirrors or large wall pieces, use fixings suitable for your wall and the weight. For electrical, structural or other specialist work, please use a qualified professional.</p>

<h2>Links to other websites</h2>
<p>We may link to other websites. We do not control them and are not responsible for their content or policies.</p>

<h2>Accuracy</h2>
<p>We work to keep the website accurate and up to date, but we cannot promise it is free from errors or omissions.</p>

@endsection