@extends('tools.layout')

@section('fields')

    {{-- STEP 1 --}}
    <div class="sgh-step">
        <h2 class="sgh-step-title"><span>1</span> Your window</h2>
        <p class="sgh-step-intro">Measure the width to cover and the drop from the rail.</p>

        @include('tools._unit')

        <div class="row gx-3">
            <div class="col-sm-6">
                @include('tools._field', [
                    'name' => 'width', 'label' => 'Width of the rail or window', 'unitKey' => 'len', 'required' => true,
                    'hint' => 'Measure the rail, which is usually a little wider than the window.',
                ])
            </div>
            <div class="col-sm-6">
                @include('tools._field', [
                    'name' => 'drop', 'label' => 'Drop (rail to where it ends)', 'unitKey' => 'len', 'required' => true,
                ])
            </div>
        </div>
    </div>

    {{-- STEP 2 --}}
    <div class="sgh-step">
        <h2 class="sgh-step-title"><span>2</span> Your curtains</h2>
        <p class="sgh-step-intro">Choose the fullness and the width of one panel.</p>

        <div class="row gx-3">
            <div class="col-sm-6 mb-3">
                <label for="f-fullness" class="form-label">Fullness <span class="text-danger" aria-hidden="true">*</span></label>
                <select id="f-fullness" name="fullness" class="form-select @error('fullness') is-invalid @enderror" data-required>
                    <option value="1.5" @selected((string) old('fullness', '2') === '1.5')>Light (1.5 times)</option>
                    <option value="2"   @selected((string) old('fullness', '2') === '2')>Standard (2 times)</option>
                    <option value="2.5" @selected((string) old('fullness', '2') === '2.5')>Luxury (2.5 times)</option>
                </select>
                <div class="sgh-field-error" data-error-for="fullness">@error('fullness'){{ $message }}@enderror</div>
                <div class="form-text">How gathered the curtain is. More fullness means more fabric.</div>
            </div>
            <div class="col-sm-6">
                @include('tools._field', [
                    'name' => 'panelw', 'label' => 'Width of one panel', 'unitKey' => 'len', 'required' => true,
                    'defaultM' => 1.4, 'defaultFt' => 4.6,
                    'hint' => 'Fabric is often 1.4 m (about 4.6 ft) wide.',
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
                <h3>Fabric width</h3>
                <p>The width of your rail multiplied by the fullness. A fullness of 2 means the curtain is twice as wide as the window when it is flat, so it folds softly when drawn.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sgh-how-card">
                <span class="sgh-how-num">2</span>
                <h3>Panels</h3>
                <p>The fabric width divided by the width of one panel, rounded up. Each panel is cut to your drop plus 30 cm for the hem and the heading.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sgh-how-card">
                <span class="sgh-how-num">3</span>
                <h3>Fabric to buy</h3>
                <p>The cut length multiplied by the number of panels, shown in metres, or in yards if you measure in feet.</p>
            </div>
        </div>
    </div>
@endsection