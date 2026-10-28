@extends('tools.layout')

@section('fields')

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
        <div class="col-sm-6">
            @include('tools._field', [
                'name' => 'pack', 'label' => 'Area one pack covers', 'unitKey' => 'area',
                'hint' => 'Optional. Printed on the box, for example 2.2 m² or 24 ft².',
            ])
        </div>
        <div class="col-sm-6">
            @include('tools._field', [
                'name' => 'price', 'label' => 'Price of one pack', 'suffix' => config('shop.currency'),
                'hint' => 'Optional. Needs the pack area, to work out the cost.',
            ])
        </div>
    </div>

@endsection