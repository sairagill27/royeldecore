@extends('tools.layout')

@php
    // Starting rows: what the visitor typed before (without JavaScript), or three empty rows
    $rows = old('items');
    if (! is_array($rows) || $rows === []) {
        $rows = [['name' => '', 'cost' => ''], ['name' => '', 'cost' => ''], ['name' => '', 'cost' => '']];
    }
@endphp

@section('fields')

    {{-- STEP 1 --}}
    <div class="sgh-step">
        <h2 class="sgh-step-title"><span>1</span> Your budget <small>(optional)</small></h2>
        <p class="sgh-step-intro">The most you want to spend on this room.</p>

        <div class="row gx-3">
            <div class="col-sm-8">
                @include('tools._field', ['name' => 'budget', 'label' => 'Total budget', 'suffix' => config('shop.currency')])
            </div>
        </div>
    </div>

    {{-- STEP 2 --}}
    <div class="sgh-step">
        <h2 class="sgh-step-title"><span>2</span> Your items</h2>
        <p class="sgh-step-intro">Add each thing you want to buy and what it costs. Empty rows are ignored.</p>

        <div class="sgh-error-line" data-error-for="items">@error('items'){{ $message }}@enderror</div>

        <div id="budgetItems" data-currency="{{ config('shop.currency') }}">
            @foreach ($rows as $i => $row)
                <div class="sgh-item-row" data-item>
                    <div class="sgh-item-name">
                        <label class="visually-hidden" for="item-name-{{ $i }}">Item name</label>
                        <input type="text" id="item-name-{{ $i }}" name="items[{{ $i }}][name]" maxlength="60"
                               value="{{ $row['name'] ?? '' }}" class="form-control" placeholder="Item, for example Sofa">
                    </div>
                    <div class="sgh-item-cost">
                        <label class="visually-hidden" for="item-cost-{{ $i }}">Cost</label>
                        <div class="input-group">
                            <input type="number" step="any" min="0" inputmode="decimal" id="item-cost-{{ $i }}"
                                   name="items[{{ $i }}][cost]" value="{{ $row['cost'] ?? '' }}" class="form-control" placeholder="Cost">
                            <span class="input-group-text">{{ config('shop.currency') }}</span>
                        </div>
                    </div>
                    <button type="button" class="sgh-item-remove" data-remove aria-label="Remove this item"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>

        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 mb-3" id="addItem">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add an item
        </button>
        <p class="form-text mb-3" id="itemLimit"></p>
    </div>

@endsection

@section('how')
    <div class="row g-3">
        <div class="col-md-4">
            <div class="sgh-how-card">
                <span class="sgh-how-num">1</span>
                <h3>Total spend</h3>
                <p>We add up the cost of every item with a cost. Rows left empty are ignored.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sgh-how-card">
                <span class="sgh-how-num">2</span>
                <h3>Money left</h3>
                <p>Your budget minus the total. If the total is higher, we show how far over budget you are.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sgh-how-card">
                <span class="sgh-how-num">3</span>
                <h3>Share of each item</h3>
                <p>Each item's cost as a percentage of the total, with the five biggest shown first, so you can see where the money goes.</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/budget.js') }}" defer></script>
@endpush