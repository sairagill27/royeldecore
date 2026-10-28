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
            @include('tools._field', ['name' => 'height', 'label' => 'Wall height', 'unitKey' => 'len', 'required' => true])
        </div>

        <div class="col-sm-6 mb-3">
            <label for="f-coats" class="form-label">Coats of paint <span class="text-danger" aria-hidden="true">*</span></label>
            <select id="f-coats" name="coats" class="form-select @error('coats') is-invalid @enderror" data-required>
                @foreach ([1, 2, 3, 4] as $n)
                    <option value="{{ $n }}" @selected((int) old('coats', 2) === $n)>{{ $n }} {{ $n === 1 ? 'coat' : 'coats' }}</option>
                @endforeach
            </select>
            <div class="sgh-field-error" data-error-for="coats">@error('coats'){{ $message }}@enderror</div>
        </div>

        <div class="col-sm-6">
            @include('tools._field', ['name' => 'doors', 'label' => 'Doors', 'default' => 1, 'step' => 1])
        </div>
        <div class="col-sm-6">
            @include('tools._field', ['name' => 'windows', 'label' => 'Windows', 'default' => 2, 'step' => 1])
        </div>

        <div class="col-sm-6">
            @include('tools._field', [
                'name' => 'coverage', 'label' => 'Paint coverage', 'unitKey' => 'cov', 'required' => true,
                'defaultM' => 10, 'defaultFt' => 108,
                'hint' => 'Printed on the tin. Typical: about 10 m² or 108 ft² per litre.',
            ])
        </div>
        <div class="col-sm-6">
            @include('tools._field', [
                'name' => 'tin', 'label' => 'Tin size', 'suffix' => 'litres', 'default' => 4, 'required' => true,
                'hint' => 'One gallon is about 3.8 litres.',
            ])
        </div>
    </div>

    {{-- The hidden 0 makes sure "unticked" is sent too --}}
    <input type="hidden" name="ceiling" value="0">
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="f-ceiling" name="ceiling" value="1" @checked(old('ceiling') == '1')>
        <label class="form-check-label" for="f-ceiling">Also paint the ceiling</label>
    </div>

@endsection