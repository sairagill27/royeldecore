@extends('tools.layout')

@section('fields')

    {{-- STEP 1 --}}
    <div class="sgh-step">
        <h2 class="sgh-step-title"><span>1</span> Your room</h2>
        <p class="sgh-step-intro">Measure the floor and the height of the walls.</p>

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
            <div class="col-sm-6">
                @include('tools._field', ['name' => 'doors', 'label' => 'Doors', 'default' => 1, 'step' => 1])
            </div>
            <div class="col-sm-6">
                @include('tools._field', ['name' => 'windows', 'label' => 'Windows', 'default' => 2, 'step' => 1])
            </div>
        </div>

        {{-- The hidden 0 makes sure "unticked" is sent too --}}
        <input type="hidden" name="ceiling" value="0">
        <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" id="f-ceiling" name="ceiling" value="1" @checked(old('ceiling') == '1')>
            <label class="form-check-label" for="f-ceiling">Also paint the ceiling</label>
        </div>
    </div>

    {{-- STEP 2 --}}
    <div class="sgh-step">
        <h2 class="sgh-step-title"><span>2</span> Your paint</h2>
        <p class="sgh-step-intro">Use the numbers printed on the tin you plan to buy.</p>

        <div class="row gx-3">
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
                @include('tools._field', [
                    'name' => 'tin', 'label' => 'Tin size', 'suffix' => 'litres', 'default' => 4, 'required' => true,
                    'hint' => 'One gallon is about 3.8 litres.',
                ])
            </div>
            <div class="col-12">
                @include('tools._field', [
                    'name' => 'coverage', 'label' => 'Paint coverage', 'unitKey' => 'cov', 'required' => true,
                    'defaultM' => 10, 'defaultFt' => 108,
                    'hint' => 'Typical: about 10 m² or 108 ft² per litre per coat.',
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
                <h3>Wall area</h3>
                <p>We add up the four walls (length and width, twice) and multiply by the height. Each door takes off 1.9 m² (about 20 ft²) and each window 1.5 m² (about 16 ft²).</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sgh-how-card">
                <span class="sgh-how-num">2</span>
                <h3>Litres of paint</h3>
                <p>Area × number of coats ÷ the coverage of your paint. We add 10% for uneven walls and touch-ups, and round up to the next 0.1 litre.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sgh-how-card">
                <span class="sgh-how-num">3</span>
                <h3>Tins to buy</h3>
                <p>The litres divided by your tin size, rounded up to whole tins, so you do not run short in the middle of a wall.</p>
            </div>
        </div>
    </div>
@endsection