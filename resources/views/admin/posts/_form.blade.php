{{-- SHARED POST FORM: used by Write and Edit. It receives $post, $categories, $allTags and $tagText.
     old('x', $post->x) = "what the admin typed after an error, otherwise the saved value". --}}

@php
    $tz = config('shop.timezone');
    // The date box wants this format: 2026-10-20T14:30 (shown in Pakistan time)
    $dateValue = old('published_at', $post->published_at?->copy()->timezone($tz)->format('Y-m-d\TH:i'));
@endphp

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> Please fix the highlighted fields and try again.
    </div>
@endif

<div class="row g-3">

    {{-- ============ LEFT: THE WRITING ============ --}}
    <div class="col-xl-8">

        <div class="sgh-panel mb-3">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" id="title" name="title" value="{{ old('title', $post->title) }}"
                       class="form-control form-control-lg @error('title') is-invalid @enderror" maxlength="200" required>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="excerpt" class="form-label">Short summary <span class="text-secondary">(shown on blog cards)</span></label>
                <textarea id="excerpt" name="excerpt" rows="2" maxlength="400"
                          class="form-control @error('excerpt') is-invalid @enderror">{{ old('excerpt', $post->excerpt) }}</textarea>
                @error('excerpt') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Write / Preview switch --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <label for="content" class="form-label mb-0">Article</label>
                <div class="btn-group btn-group-sm" role="group" aria-label="Editor mode">
                    <button type="button" class="btn btn-outline-primary active" id="tabWrite">Write</button>
                    <button type="button" class="btn btn-outline-primary" id="tabPreview">Preview</button>
                </div>
            </div>

            {{-- WRITE PANE --}}
            <div id="paneWrite">
                <div class="sgh-editor-bar" role="toolbar" aria-label="Formatting">
                    <button type="button" data-md="bold"    title="Bold"><i class="bi bi-type-bold"></i></button>
                    <button type="button" data-md="italic"  title="Italic"><i class="bi bi-type-italic"></i></button>
                    <button type="button" data-md="heading" title="Heading"><i class="bi bi-type-h2"></i></button>
                    <button type="button" data-md="link"    title="Link"><i class="bi bi-link-45deg"></i></button>
                    <button type="button" data-md="list"    title="Bullet list"><i class="bi bi-list-ul"></i></button>
                    <button type="button" data-md="quote"   title="Quote"><i class="bi bi-quote"></i></button>
                    <span class="ms-auto small text-secondary" id="wordCount" aria-live="polite"></span>
                </div>
                <textarea id="content" name="content" rows="18" maxlength="60000"
                          class="form-control sgh-editor @error('content') is-invalid @enderror" required>{{ old('content', $post->content) }}</textarea>
                @error('content') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                <div class="form-text">
                    Formatting: <code>## Heading</code>, <code>**bold**</code>, <code>*italic*</code>,
                    <code>[link text](https://...)</code>, <code>- list item</code>, <code>&gt; quote</code>.
                    HTML typed here is removed for safety.
                </div>
            </div>

            {{-- PREVIEW PANE --}}
            <div id="panePreview" class="sgh-prose sgh-preview" hidden></div>
        </div>

        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div><h2>Search engines (SEO)</h2><p>Optional. Leave empty and we fill them in for you.</p></div>
            </div>

            <div class="mb-3">
                <label for="slug" class="form-label">Web address name (slug)</label>
                <div class="input-group">
                    <span class="input-group-text">/blog/</span>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $post->slug) }}"
                           class="form-control @error('slug') is-invalid @enderror"
                           placeholder="small-bedroom-ideas" maxlength="220">
                    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-text">Lowercase letters, numbers and dashes. Empty = made from the title.</div>
            </div>

            <div class="mb-3">
                <label for="seo_title" class="form-label">SEO title</label>
                <input type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $post->seo_title) }}"
                       class="form-control @error('seo_title') is-invalid @enderror" maxlength="255">
                @error('seo_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-0">
                <label for="meta_description" class="form-label">Meta description</label>
                <textarea id="meta_description" name="meta_description" rows="3" maxlength="320"
                          class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $post->meta_description) }}</textarea>
                @error('meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">The short text under your link in Google (up to 320 characters).</div>
            </div>
        </div>
    </div>

    {{-- ============ RIGHT: PUBLISH, ORGANISE, PHOTO ============ --}}
    <div class="col-xl-4">

        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head"><div><h2>Publish</h2></div></div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="draft"     @selected(old('status', $post->status) === 'draft')>Draft (hidden)</option>
                    <option value="published" @selected(old('status', $post->status) === 'published')>Published</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="published_at" class="form-label">Publish date and time</label>
                <input type="datetime-local" id="published_at" name="published_at" value="{{ $dateValue }}"
                       class="form-control @error('published_at') is-invalid @enderror">
                @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">
                    Times are in {{ $tz }}. Empty = publish now. A future date schedules the post:
                    it goes live at that time.
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary rounded-pill">
                    <i class="bi bi-check2 me-1"></i> Save post
                </button>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary rounded-pill">Cancel</a>
            </div>
        </div>

        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head"><div><h2>Organise</h2></div></div>

            <div class="mb-3">
                <label for="blog_category_id" class="form-label">Category</label>
                <select id="blog_category_id" name="blog_category_id"
                        class="form-select @error('blog_category_id') is-invalid @enderror">
                    <option value="">No category</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) old('blog_category_id', $post->blog_category_id) === (string) $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('blog_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-0">
                <label for="tags" class="form-label">Tags</label>
                <input type="text" id="tags" name="tags" value="{{ old('tags', $tagText) }}"
                       class="form-control @error('tags') is-invalid @enderror"
                       placeholder="cozy, small space, budget" maxlength="600" autocomplete="off">
                @error('tags') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Separate with commas. Up to 10 tags.</div>

                @if ($allTags->isNotEmpty())
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        @foreach ($allTags as $tagName)
                            <button type="button" class="sgh-chip border-0" data-add-tag="{{ $tagName }}">+ {{ $tagName }}</button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="sgh-panel">
            <div class="sgh-panel-head"><div><h2>Featured image</h2><p>JPG, PNG or WebP, up to 2 MB.</p></div></div>

            <div class="sgh-upload-preview mb-3" id="imagePreview">
                @if ($post->featured_image)
                    <img src="{{ asset('storage/' . $post->featured_image) }}" alt="Current featured image">
                @else
                    <i class="bi bi-image" aria-hidden="true"></i>
                @endif
            </div>

            <label for="featured_image" class="form-label visually-hidden">Choose a photo</label>
            <input type="file" id="featured_image" name="featured_image" accept="image/jpeg,image/png,image/webp"
                   class="form-control @error('featured_image') is-invalid @enderror">
            @error('featured_image') <div class="invalid-feedback">{{ $message }}</div> @enderror

            <div class="mt-3">
                <label for="featured_image_alt" class="form-label">Describe the photo</label>
                <input type="text" id="featured_image_alt" name="featured_image_alt"
                       value="{{ old('featured_image_alt', $post->featured_image_alt) }}"
                       class="form-control @error('featured_image_alt') is-invalid @enderror" maxlength="160"
                       placeholder="A small bedroom with a grey bed and a round mirror">
                @error('featured_image_alt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Read aloud by screen readers, and used by search engines.</div>
            </div>

            @if ($post->featured_image)
                <input type="hidden" name="remove_image" value="0">
                <div class="form-check mt-3 mb-0">
                    <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                    <label class="form-check-label small" for="remove_image">Remove the current photo</label>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- The editor's buttons, preview, counter, tag buttons and photo preview --}}
<script>
    (function () {
        var ta = document.getElementById('content');
        var words = document.getElementById('wordCount');

        // ----- word count and reading time -----
        function count() {
            var n = ta.value.trim().split(/\s+/).filter(Boolean).length;
            words.textContent = n + ' words · ' + Math.max(1, Math.ceil(n / 200)) + ' min read';
        }
        ta.addEventListener('input', count);
        count();

        // ----- toolbar: puts Markdown around the selected text -----
        function wrap(before, after, placeholder) {
            var s = ta.selectionStart, e = ta.selectionEnd;
            ta.setRangeText(before + (ta.value.slice(s, e) || placeholder) + after, s, e, 'select');
            ta.focus(); count();
        }
        function linePrefix(prefix) {
            var start = ta.value.lastIndexOf('\n', ta.selectionStart - 1) + 1;
            ta.setRangeText(prefix, start, start, 'end');
            ta.focus(); count();
        }
        var actions = {
            bold:    function () { wrap('**', '**', 'bold text'); },
            italic:  function () { wrap('*', '*', 'italic text'); },
            heading: function () { linePrefix('## '); },
            link:    function () { wrap('[', '](https://)', 'link text'); },
            list:    function () { linePrefix('- '); },
            quote:   function () { linePrefix('> '); }
        };
        document.querySelectorAll('[data-md]').forEach(function (b) {
            b.addEventListener('click', function () { actions[b.dataset.md](); });
        });

        // ----- Write / Preview -----
        var paneWrite = document.getElementById('paneWrite');
        var panePreview = document.getElementById('panePreview');
        var tabWrite = document.getElementById('tabWrite');
        var tabPreview = document.getElementById('tabPreview');

        tabWrite.addEventListener('click', function () {
            paneWrite.hidden = false; panePreview.hidden = true;
            tabWrite.classList.add('active'); tabPreview.classList.remove('active');
        });

        tabPreview.addEventListener('click', async function () {
            paneWrite.hidden = true; panePreview.hidden = false;
            tabPreview.classList.add('active'); tabWrite.classList.remove('active');
            panePreview.innerHTML = '<p class="text-secondary">Loading preview…</p>';

            try {
                // The server turns the Markdown into safe HTML (raw HTML is removed there)
                var res = await fetch(@json(route('admin.posts.preview')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token())
                    },
                    body: JSON.stringify({ content: ta.value })
                });
                if (!res.ok) { throw new Error('bad response'); }
                var data = await res.json();
                panePreview.innerHTML = data.html || '<p class="text-secondary">Nothing to preview yet.</p>';
            } catch (err) {
                panePreview.innerHTML = '<p class="text-danger">The preview could not be loaded. Please try again.</p>';
            }
        });

        // ----- quick-add tag buttons -----
        var tagBox = document.getElementById('tags');
        document.querySelectorAll('[data-add-tag]').forEach(function (b) {
            b.addEventListener('click', function () {
                var list = tagBox.value.split(',').map(function (t) { return t.trim(); }).filter(Boolean);
                var exists = list.some(function (t) { return t.toLowerCase() === b.dataset.addTag.toLowerCase(); });
                if (!exists) { list.push(b.dataset.addTag); }
                tagBox.value = list.join(', ');
            });
        });

        // ----- photo preview (the server checks the file again) -----
        var img = document.getElementById('featured_image');
        var box = document.getElementById('imagePreview');
        img.addEventListener('change', function () {
            var file = img.files[0];
            if (!file) { return; }
            if (file.size > 2 * 1024 * 1024) {
                alert('The photo must be smaller than 2 MB.');
                img.value = '';
                return;
            }
            box.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="Preview of the chosen photo">';
        });
    })();
</script>