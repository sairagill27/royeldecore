{{-- Feet or metres. Feet is the default. --}}
<div class="mb-3">
    <span class="form-label d-block">Measuring in</span>
    <div class="btn-group" role="group" aria-label="Unit of measurement">
        <input type="radio" class="btn-check" name="unit" id="unit-ft" value="ft" autocomplete="off" @checked(old('unit', 'ft') === 'ft')>
        <label class="btn btn-outline-primary btn-sm px-3" for="unit-ft">Feet</label>

        <input type="radio" class="btn-check" name="unit" id="unit-m" value="m" autocomplete="off" @checked(old('unit', 'ft') === 'm')>
        <label class="btn btn-outline-primary btn-sm px-3" for="unit-m">Metres</label>
    </div>
</div>