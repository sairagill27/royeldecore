@extends('tools.layout')

@section('fields')

    {{-- STEP 1 --}}
    <div class="sgh-step">
        <h2 class="sgh-step-title"><span>1</span> Your room</h2>
        <p class="sgh-step-intro">Measure the length and width of the floor.</p>

        @include('tools._unit')

        <div class="row gx-3">
            <div class="col-sm-6">
                @include('tools._field', ['name' => 'length', 'label' => 'Room length', 'unitKey' => 'len', 'required' => true])
            </div>
            <div class="col-sm-6">
                @include('tools._field', ['name' => 'width', 'label' => 'Room width', 'unitKey' => 'len', 'required' => true])
            </div>
            <div class="col-sm-6">
                @include('tools._field', [
                    'name' => 'waste', 'label' => 'Allowance for cuts and waste', 'suffix' => '%', 'default' => 10,
                    'hint' => 'About 10% for a plain layout, 15% for diagonal or patterned.',
                ])
            </div>
        </div>
    </div>

    {{-- STEP 2 --}}
    <div class="sgh-step">
        <h2 class="sgh-step-title"><span>2</span> Your flooring <small>(optional)</small></h2>
        <p class="sgh-step-intro">Add the pack details to see how many packs to buy and what they cost.</p>

        <div class="row gx-3">
            <div class="col-sm-6">
                @include('tools._field', [
                    'name' => 'pack', 'label' => 'Area one pack covers', 'unitKey' => 'area',
                    'hint' => 'Printed on the box, for example 2.2 m² or 24 ft².',
                ])
            </div>
            <div class="col-sm-6">
                @include('tools._field', [
                    'name' => 'price', 'label' => 'Price of one pack', 'suffix' => config('shop.currency'),
                    'hint' => 'Needs the pack area, to work out the cost.',
                ])
            </div>
        </div>
    </div>

@endsection

@section('how')
    <div class="row g-3">
        <div class="col-md-4">
            <div class="sgh-how-card">
                <span class="sgh-how-num">1</span>
                <h3>Room area</h3>
                <p>The length multiplied by the width gives the floor area.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sgh-how-card">
                <span class="sgh-how-num">2</span>
                <h3>Allowance for waste</h3>
                <p>Cutting around walls and corners wastes some material, so we add your allowance (10% unless you change it) to the area.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sgh-how-card">
                <span class="sgh-how-num">3</span>
                <h3>Packs and cost</h3>
                <p>The area to buy divided by what one pack covers, rounded up to whole packs. Multiply by the pack price for the estimated cost.</p>
            </div>
        </div>
    </div>
@endsection