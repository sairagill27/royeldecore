{{-- SHARED CATEGORY FORM: used by Add and Edit. It receives $category.
     old('x', $category->x) = "what the admin typed after an error, otherwise the saved value". --}}

@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i> Please fix the highlighted fields and try again.
</div>
@endif

<div class="row g-3">

    {{-- LEFT: details --}}
    <div class="col-lg-8">
        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div>
                    <h2>Category details</h2>
                </div>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}"
                    class="form-control @error('name') is-invalid @enderror" maxlength="100" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="slug" class="form-label">Web address name (slug)</label>
                <div class="input-group">
                    <span class="input-group-text">/shop/</span>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $category->slug) }}"
                        class="form-control @error('slug') is-invalid @enderror"
                        placeholder="wall-clocks" maxlength="120">
                    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-text">Lowercase letters, numbers and dashes. Empty = made from the name.
                    Changing it later changes the category's web address.</div>
            </div>

            <div class="mb-0">
                <label for="description" class="form-label">Description <span class="text-secondary">(optional)</span></label>
                <textarea id="description" name="description" rows="4" maxlength="500"
                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Shown at the top of the category page in the shop.</div>
            </div>
        </div>
    </div>

    {{-- RIGHT: visibility, order, photo --}}
    <div class="col-lg-4">

        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head">
                <div>
                    <h2>Visibility</h2>
                </div>
            </div>

            {{-- The hidden 0 makes sure "unchecked" is sent too --}}
            <input type="hidden" name="is_active" value="0">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
                    @checked(old('is_active', $category->is_active))>
                <label class="form-check-label" for="is_active">Show in the shop</label>
            </div>

            <label for="sort_order" class="form-label">Sort order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}"
                class="form-control @error('sort_order') is-invalid @enderror" min="0" max="9999">
            @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Smaller numbers come first (1, 2, 3...).</div>
        </div>

        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div>
                    <h2>Photo</h2>
                    <p>JPG, PNG or WebP, up to 2 MB.</p>
                </div>
            </div>

            <div class="sgh-upload-preview mb-3" id="imagePreview">
                @if ($category->image)
                <img src="{{ asset('storage/' . $category->image) }}" alt="Current category photo">
                @else
                <i class="bi bi-image" aria-hidden="true"></i>
                @endif
            </div>

            <label for="image" class="form-label visually-hidden">Choose a photo</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
                class="form-control @error('image') is-invalid @enderror">
            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror

            @if ($category->image)
            <input type="hidden" name="remove_image" value="0">
            <div class="form-check mt-3 mb-0">
                <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                <label class="form-check-label small" for="remove_image">Remove the current photo</label>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mt-4">
    <button type="submit" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-check2 me-1"></i> Save category
    </button>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</div>

{{-- Shows the chosen photo before saving (the server checks the file again) --}}
<script>
    (function() {
        var input = document.getElementById('image');
        var box = document.getElementById('imagePreview');
        if (!input) return;

        input.addEventListener('change', function() {
            var file = input.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                alert('The photo must be smaller than 2 MB.');
                input.value = '';
                return;
            }
            box.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="Preview of the chosen photo">';
        });
    })();
</script>