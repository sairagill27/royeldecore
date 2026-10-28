{{-- ONE NUMBER BOX. Options: name, label, required, unitKey (len, area or cov: the unit text follows the
     feet/metres choice), suffix (fixed text), step, hint, default or defaultM and defaultFt. --}}
@php
    $isFeet = old('unit', 'ft') === 'ft';
    $startM = $defaultM  ?? ($default ?? '');
    $startF = $defaultFt ?? ($default ?? '');
    $shown  = old($name, $isFeet ? $startF : $startM);
    $dynamic = [
        'len'  => $isFeet ? 'ft'  : 'm',
        'area' => $isFeet ? 'ft²' : 'm²',
        'cov'  => $isFeet ? 'ft² per litre' : 'm² per litre',
    ];
@endphp

<div class="mb-3">
    <label for="f-{{ $name }}" class="form-label">{{ $label }}@if (! empty($required)) <span class="text-danger" aria-hidden="true">*</span>@endif</label>
    <div class="input-group">
        <input type="number" step="{{ $step ?? 'any' }}" min="0" inputmode="decimal"
               id="f-{{ $name }}" name="{{ $name }}" value="{{ $shown }}"
               class="form-control @error($name) is-invalid @enderror"
               @if (! empty($required)) data-required @endif
               @if (isset($defaultM)) data-default-m="{{ $defaultM }}" data-default-ft="{{ $defaultFt }}" @endif>
        @if (! empty($unitKey))
            <span class="input-group-text" data-u="{{ $unitKey }}">{{ $dynamic[$unitKey] }}</span>
        @elseif (! empty($suffix))
            <span class="input-group-text">{{ $suffix }}</span>
        @endif
    </div>
    <div class="sgh-field-error" data-error-for="{{ $name }}">@error($name){{ $message }}@enderror</div>
    @if (! empty($hint))
        <div class="form-text">{{ $hint }}</div>
    @endif
</div>