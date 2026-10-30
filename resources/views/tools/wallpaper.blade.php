@extends('tools.layout')

@section('fields')

    {{-- STEP 1 --}}
    <div class="sgh-step">
        <h2 class="sgh-step-title"><span>1</span> Your room</h2>
        <p class="sgh-step-intro">Measure the walls you want to cover.</p>

        @include('tools._unit')

        <div class="row gx-3">
            <div class="col-sm-6">
                @include('tools._field', ['name' => 'length', 'label' => 'Room length', 'unitKey' => 'len', 'required' => true])
            </div>
            <div class="col-sm-6">
                @include('tools._field', ['name' => 'width', 'label' => 'Room width', 'unitKey' => 'len', 'required' => true])
            </div>
            <div class="col-sm-6">
                @include('tools._field', ['name' => 'height', 'label' => 'Wall height', 'unitKey' => 'len', 'required' => true])
            </div>
            <div class="col-sm-3">
                @include('tools._field', ['name' => 'doors', 'label' => 'Doors', 'default' => 1, 'step' => 1])
            </div>
            <div class="col-sm-3">
                @include('tools._field', ['name' => 'windows', 'label' => 'Windows', 'default' => 2, 'step' => 1])
            </div>
        </div>
    </div>

    {{-- STEP 2 --}}
    <div class="sgh-step">
        <h2 class="sgh-step-title"><span>2</span> Your wallpaper</h2>
        <p class="sgh-step-intro">Use the roll size printed on the label.</p>

        <div class="row gx-3">
            <div class="col-sm-6">
                @include('tools._field', [
                    'name' => 'rollw', 'label' => 'Roll width', 'unitKey' => 'len', 'required' => true,
                    'defaultM' => 0.53, 'defaultFt' => 1.75,
                    'hint' => 'A standard roll is 0.53 m (about 1.75 ft) wide.',
                ])
            </div>
            <div class="col-sm-6">
                @include('tools._field', [
                    'name' => 'rolll', 'label' => 'Roll length', 'unitKey' => 'len', 'required' => true,
                    'defaultM' => 10, 'defaultFt' => 33,
                    'hint' => 'A standard roll is 10 m (about 33 ft) long.',
                ])
            </div>
            <div class="col-sm-6">
                @include('tools._field', [
                    'name' => 'repeat', 'label' => 'Pattern repeat', 'unitKey' => 'len', 'default' => 0,
                    'hint' => 'Printed on the roll. Use 0 for a plain paper.',
                ])
            </div>
            <div class="col-sm-6">
                @include('tools._field', [
                    'name' => 'price', 'label' => 'Price of one roll', 'suffix' => config('shop.currency'),
                    'hint' => 'Optional, to work out the cost.',
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
                <h3>Strips per roll</h3>
                <p>Each strip is as tall as your wall, plus the pattern repeat so the pattern lines up. The roll length divided by the strip height, rounded down, gives the strips in one roll.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sgh-how-card">
                <span class="sgh-how-num">2</span>
                <h3>Strips needed</h3>
                <p>The distance around the room divided by the roll width, rounded up. Doors and windows save a few strips, so we take off the width they cover.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sgh-how-card">
                <span class="sgh-how-num">3</span>
                <h3>Rolls to buy</h3>
                <p>The strips needed divided by the strips in one roll, rounded up to whole rolls. Multiply by the roll price for the estimated cost.</p>
            </div>
        </div>
    </div>
@endsection