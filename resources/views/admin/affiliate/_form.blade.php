{{-- SHARED LINK FORM: used by Add and Edit. It receives $link, $sections and $blogCategories.
     old('x', $link->x) = "what the admin typed after an error, otherwise the saved value". --}}

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> Please fix the highlighted fields and try again.
    </div>
@endif

<div class="row g-3">

    <div class="col-xl-8">
        <div class="sgh-panel mb-3">
            <div class="row g-3">
                <div class="col-md-7">
                    <label for="name" class="form-label">Name of the product</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $link->name) }}" maxlength="120" required
                           class="form-control @error('name') is-invalid @enderror" placeholder="Warm LED bulb set">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-5">
                    <label for="merchant" class="form-label">Store</label>
                    <input type="text" id="merchant" name="merchant" value="{{ old('merchant', $link->merchant) }}" maxlength="60" required
                           class="form-control @error('merchant') is-invalid @enderror" placeholder="Daraz">
                    @error('merchant') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-3">
                <label for="url" class="form-label">Affiliate link</label>
                <input type="text" id="url" name="url" value="{{ old('url', $link->url) }}" maxlength="2000" required
                       class="form-control @error('url') is-invalid @enderror" placeholder="https://..." autocomplete="off">
                @error('url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Copy it exactly from your affiliate program's dashboard. It must start with https://.</div>
            </div>

            <div class="mt-3">
                <label for="description" class="form-label">Short note <span class="text-secondary">(optional)</span></label>
                <input type="text" id="description" name="description" value="{{ old('description', $link->description) }}" maxlength="200"
                       class="form-control @error('description') is-invalid @enderror" placeholder="Soft, warm light for a cosy corner.">
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Do not write prices. They change. Visitors are told to check the store.</div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-md-6">
                    <label for="section" class="form-label">Section on the Recommended page</label>
                    <select id="section" name="section" class="form-select @error('section') is-invalid @enderror">
                        <option value="">No section</option>
                        @foreach ($sections as $name)
                            <option value="{{ $name }}" @selected(old('section', $link->section) === $name)>{{ $name }}</option>
                        @endforeach
                        {{-- A section typed in an older version can stay --}}
                        @if ($link->section && ! in_array($link->section, $sections, true))
                            <option value="{{ $link->section }}" @selected(old('section', $link->section) === $link->section)>{{ $link->section }} (older value)</option>
                        @endif
                    </select>
                    @error('section') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Tip: link to things you do not sell yourself. A link to a product you also sell sends the buyer to another store.</div>
                </div>
                <div class="col-md-6">
                    <label for="blog_category_id" class="form-label">Show under articles in <span class="text-secondary">(optional)</span></label>
                    <select id="blog_category_id" name="blog_category_id" class="form-select @error('blog_category_id') is-invalid @enderror">
                        <option value="">No blog category</option>
                        @foreach ($blogCategories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('blog_category_id', $link->blog_category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('blog_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Up to 3 matching links appear under each article of this category.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">

        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head"><div><h2>Status</h2></div></div>

            <div class="mb-3">
                <label for="status" class="form-label">Visible to visitors?</label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                    @foreach (\App\Models\AffiliateLink::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', $link->status ?: 'active') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Paused and Expired links are hidden, and their short address stops working.</div>
            </div>

            <div class="mb-3">
                <label for="expires_at" class="form-label">End date <span class="text-secondary">(optional)</span></label>
                <input type="date" id="expires_at" name="expires_at" value="{{ old('expires_at', $link->expires_at?->toDateString()) }}"
                       class="form-control @error('expires_at') is-invalid @enderror">
                @error('expires_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">For an offer that ends. After this day the link hides itself.</div>
            </div>

            {{-- The hidden 0 makes sure "unticked" is sent too --}}
            <input type="hidden" name="is_featured" value="0">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" @checked(old('is_featured', $link->is_featured))>
                <label class="form-check-label" for="is_featured">Featured pick</label>
                <div class="form-text">Shown first on the Recommended page (up to 3).</div>
            </div>

            <input type="hidden" name="is_direct" value="0">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="is_direct" name="is_direct" value="1" @checked(old('is_direct', $link->is_direct))>
                <label class="form-check-label" for="is_direct">Direct link</label>
                <div class="form-text">Goes straight to the store, without passing through this website. Clicks are then not counted here.
                    Tick it if your program has rules about redirect links. If in doubt, read the program's terms.</div>
            </div>
        </div>

        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head"><div><h2>Short name</h2></div></div>
            <div class="input-group">
                <span class="input-group-text">/go/</span>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $link->slug) }}" maxlength="80"
                       class="form-control @error('slug') is-invalid @enderror" placeholder="warm-led-bulbs" autocomplete="off">
                @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-text">
                Empty = made from the name.
                @if ($link->exists)
                    Your shortcode: <code>[[affiliate:{{ $link->slug }}]]</code>.
                    Changing the short name stops the old shortcode from working.
                @endif
            </div>
        </div>

        <div class="sgh-panel">
            <div class="sgh-panel-head"><div><h2>Photo</h2><p>Optional. JPG, PNG or WebP, up to 2 MB. Use your own photo or one the program allows.</p></div></div>

            @if ($link->image)
                <div class="sgh-upload-preview mb-3"><img src="{{ asset('storage/' . $link->image) }}" alt="Current photo"></div>
            @endif

            <label for="image" class="form-label visually-hidden">Choose a photo</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
                   class="form-control @error('image') is-invalid @enderror">
            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror

            @if ($link->image)
                <input type="hidden" name="remove_image" value="0">
                <div class="form-check mt-3 mb-0">
                    <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                    <label class="form-check-label small" for="remove_image">Remove the current photo</label>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- SAVE BAR: at the very end of the page --}}
<div class="sgh-form-actions">
    <a href="{{ route('admin.affiliate-links.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
    <button type="submit" class="btn btn-primary rounded-pill px-5"><i class="bi bi-check2 me-1"></i> Save link</button>
</div>