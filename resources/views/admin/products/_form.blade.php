{{-- SHARED PRODUCT FORM: used by the Add page and the Edit page.
     It receives $product and $categories. old('x', $product->x) means:
     "show what the admin typed if there was an error, otherwise the saved value". --}}

@if ($errors->any())
<div class="alert alert-danger" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i> Please fix the highlighted fields and try again.
</div>
@endif

<div class="row g-3">

    {{-- ============ LEFT: MAIN DETAILS ============ --}}
    <div class="col-lg-8">

        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head">
                <div>
                    <h2>Product details</h2>
                </div>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Product name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}"
                    class="form-control @error('name') is-invalid @enderror" maxlength="150" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-0">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" rows="7" maxlength="5000"
                    class="form-control @error('description') is-invalid @enderror" required>{{ old('description', $product->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Plain text. Line breaks are kept. Customers see this on the product page.</div>
            </div>
        </div>

        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div>
                    <h2>Search engines (SEO)</h2>
                    <p>Optional. Leave empty and we fill them in for you.</p>
                </div>
            </div>

            <div class="mb-3">
                <label for="slug" class="form-label">Web address name (slug)</label>
                <div class="input-group">
                    <span class="input-group-text">/products/</span>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $product->slug) }}"
                        class="form-control @error('slug') is-invalid @enderror"
                        placeholder="acrylic-islamic-wall-art" maxlength="160">
                    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-text">Lowercase letters, numbers and dashes. Empty = made from the name.</div>
            </div>

            <div class="mb-3">
                <label for="seo_title" class="form-label">SEO title</label>
                <input type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $product->seo_title) }}"
                    class="form-control @error('seo_title') is-invalid @enderror" maxlength="255">
                @error('seo_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-0">
                <label for="meta_description" class="form-label">Meta description</label>
                <textarea id="meta_description" name="meta_description" rows="3" maxlength="320"
                    class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $product->meta_description) }}</textarea>
                @error('meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">The short text under your link in Google (up to 320 characters).</div>
            </div>
        </div>
    </div>

    {{-- ============ RIGHT: PUBLISH, PRICE, PHOTO ============ --}}
    <div class="col-lg-4">

        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head">
                <div>
                    <h2>Publish</h2>
                </div>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="active" @selected(old('status', $product->status) === 'active')>Active (visible in the shop)</option>
                    <option value="draft" @selected(old('status', $product->status) === 'draft')>Draft (hidden)</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- The hidden 0 makes sure "unchecked" is sent too --}}
            <input type="hidden" name="is_featured" value="0">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch" id="is_featured" name="is_featured" value="1"
                    @checked(old('is_featured', $product->is_featured))>
                <label class="form-check-label" for="is_featured">Featured on the homepage</label>
            </div>
        </div>

        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head">
                <div>
                    <h2>Organise</h2>
                </div>
            </div>

            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                    <option value="">Choose a category…</option>
                    @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected((string) old('category_id', $product->category_id) === (string) $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-0">
                <label for="sku" class="form-label">SKU <span class="text-secondary">(stock code, optional)</span></label>
                <input type="text" id="sku" name="sku" value="{{ old('sku', $product->sku) }}"
                    class="form-control @error('sku') is-invalid @enderror" maxlength="50" placeholder="WD-001">
                @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head">
                <div>
                    <h2>Price and stock</h2>
                </div>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Price ({{ config('shop.currency') }})</label>
                <input type="number" id="price" name="price" value="{{ old('price', $product->price) }}"
                    class="form-control @error('price') is-invalid @enderror" min="0" step="0.01" required>
                @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-0">
                <label for="stock" class="form-label">Stock (pieces available)</label>
                <input type="number" id="stock" name="stock" value="{{ old('stock', $product->stock) }}"
                    class="form-control @error('stock') is-invalid @enderror" min="0" step="1" required>
                @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div>
                    <h2>Photo</h2>
                    <p>JPG, PNG or WebP, up to 2 MB.</p>
                </div>
            </div>

            {{-- Preview: the saved photo, or a placeholder. Choosing a file replaces it. --}}
            <div class="sgh-upload-preview mb-3" id="imagePreview">
                @if ($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" alt="Current product photo">
                @else
                <i class="bi bi-image" aria-hidden="true"></i>
                @endif
            </div>

            <label for="image" class="form-label visually-hidden">Choose a photo</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
                class="form-control @error('image') is-invalid @enderror">
            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror

            @if ($product->image)
            <input type="hidden" name="remove_image" value="0">
            <div class="form-check mt-3 mb-0">
                <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                <label class="form-check-label small" for="remove_image">Remove the current photo</label>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- SAVE BUTTONS --}}
<div class="d-flex flex-wrap gap-2 mt-4">
    <button type="submit" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-check2 me-1"></i> Save product
    </button>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</div>

{{-- Shows the chosen photo before saving, and warns about files over 2 MB.
     (The server checks the file again. This is only for convenience.) --}}
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