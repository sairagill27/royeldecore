{{-- PHOTO GALLERY: extra photos for one product.
     It sits OUTSIDE the product form above it (a form cannot hold another form). --}}
@php
    $limit = \App\Http\Controllers\Admin\ProductGalleryController::LIMIT;
    $left  = max(0, $limit - $product->images->count());
@endphp

<div class="sgh-panel mt-3">
    <div class="sgh-panel-head">
        <div>
            <h2>Photo gallery</h2>
            <p>Extra photos shown under the main photo on the product page. Up to {{ $limit }}, JPG, PNG or WebP, 2 MB each.</p>
        </div>
    </div>

    @if ($product->images->isNotEmpty())
        <div class="row g-3 mb-3">
            @foreach ($product->images as $photo)
                <div class="col-4 col-md-3 col-xl-2">
                    <div class="position-relative">
                        <div class="ratio ratio-1x1 rounded-3 overflow-hidden border">
                            <img src="{{ asset('storage/' . $photo->path) }}" alt="Gallery photo {{ $loop->iteration }}"
                                 class="object-fit-cover" loading="lazy">
                        </div>

                        {{-- @csrf = security token. @method('DELETE') tells Laravel "this removes it". --}}
                        <form action="{{ route('admin.products.gallery.destroy', [$product, $photo]) }}" method="POST"
                              class="position-absolute top-0 end-0 m-1"
                              onsubmit="return confirm('Remove this photo?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light btn-sm shadow-sm text-danger"
                                    aria-label="Remove gallery photo {{ $loop->iteration }}">
                                <i class="bi bi-trash" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-secondary small">No extra photos yet.</p>
    @endif

    @if ($left > 0)
        {{-- enctype="multipart/form-data" is REQUIRED for photo upload --}}
        <form action="{{ route('admin.products.gallery.store', $product) }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            <label for="gallery-photos" class="form-label">
                Add photos <span class="text-secondary">(choose several at once, room for {{ $left }} more)</span>
            </label>
            <div class="input-group">
                <input type="file" id="gallery-photos" name="photos[]" multiple required
                       accept="image/jpeg,image/png,image/webp" class="form-control">
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>

            @error('photos') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            @foreach ($errors->get('photos.*') as $messages)
                @foreach ($messages as $message)
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @endforeach
            @endforeach
        </form>
    @else
        <p class="small text-secondary mb-0">The gallery is full ({{ $limit }} photos). Remove one to add another.</p>
    @endif
</div>