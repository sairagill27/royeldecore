{{-- SHARED POST FORM: used by Write and Edit. It receives $post, $categories, $allTags and $tagText.
     old('x', $post->x) = "what the admin typed after an error, otherwise the saved value". --}}

@php
    $tz = config('shop.timezone');
    // The date box wants this format: 2026-10-20T14:30 (shown in Pakistan time)
    $dateValue = old('published_at', $post->published_at?->copy()->timezone($tz)->format('Y-m-d\TH:i'));
@endphp

{{-- Settings for the script at the bottom (kept here so no Blade code sits inside the script) --}}
<div id="seoConfig" hidden
     data-preview-url="{{ route('admin.posts.preview') }}"
     data-upload-url="{{ route('admin.posts.images') }}"
     data-csrf="{{ csrf_token() }}"
     data-blog-url="{{ url('/blog') }}"
     data-site="{{ rtrim(url('/'), '/') }}"
     data-had-image="{{ $post->featured_image ? '1' : '0' }}"></div>

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
                    <button type="button" data-md="h2"      title="Heading (H2)"><i class="bi bi-type-h2"></i></button>
                    <button type="button" data-md="h3"      title="Subheading (H3)"><i class="bi bi-type-h3"></i></button>
                    <button type="button" data-md="link"    title="Link"><i class="bi bi-link-45deg"></i></button>
                    <button type="button" data-md="image"   title="Insert image"><i class="bi bi-image"></i></button>
                    <button type="button" data-md="list"    title="Bullet list"><i class="bi bi-list-ul"></i></button>
                    <button type="button" data-md="quote"   title="Quote"><i class="bi bi-quote"></i></button>
                    <span class="ms-auto small text-secondary" id="wordCount" aria-live="polite"></span>
                </div>
                <textarea id="content" name="content" rows="18" maxlength="60000"
                          class="form-control sgh-editor @error('content') is-invalid @enderror" required>{{ old('content', $post->content) }}</textarea>
                @error('content') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                <div class="form-text" id="pasteNote" role="status" aria-live="polite"></div>
                <div class="form-text">
                    Formatting: <code>## Heading</code>, <code>**bold**</code>, <code>*italic*</code>,
                    <code>[link text](https://...)</code>, <code>- list item</code>, <code>&gt; quote</code>.
                    You can paste from Word, Google Docs or a web page: headings, bold, lists and links are kept.
                    HTML typed here is removed for safety.
                </div>
            </div>

            {{-- PREVIEW PANE --}}
            <div id="panePreview" class="sgh-prose sgh-preview" hidden></div>
        </div>

        {{-- ============ SEARCH ENGINES (SEO) ============ --}}
        <div class="sgh-panel">
            <div class="sgh-panel-head">
                <div><h2>Search engines (SEO)</h2><p>How this article looks in Google. Empty boxes are filled in automatically.</p></div>
            </div>

            {{-- GOOGLE PREVIEW: updates as you type (shown as plain text) --}}
            <div class="sgh-serp mb-4">
                <p class="sgh-serp-label">Google preview</p>
                <div class="sgh-serp-url" id="serpUrl"></div>
                <div class="sgh-serp-title" id="serpTitle"></div>
                <div class="sgh-serp-desc" id="serpDesc"></div>
            </div>

            <div class="mb-3">
                <label for="focus_keyword" class="form-label">Focus keyword</label>
                <input type="text" id="focus_keyword" name="focus_keyword" value="{{ old('focus_keyword', $post->focus_keyword) }}"
                       class="form-control @error('focus_keyword') is-invalid @enderror" maxlength="80"
                       placeholder="small bedroom ideas" autocomplete="off">
                @error('focus_keyword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">The word or short phrase people would search for to find this article.</div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-baseline">
                    <label for="seo_title" class="form-label">SEO title</label>
                    <span class="sgh-len" id="lenTitle"></span>
                </div>
                <input type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $post->seo_title) }}"
                       class="form-control @error('seo_title') is-invalid @enderror" maxlength="255">
                @error('seo_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Empty = the article title. Aim for 30 to 60 characters.</div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-baseline">
                    <label for="meta_description" class="form-label">Meta description</label>
                    <span class="sgh-len" id="lenMeta"></span>
                </div>
                <textarea id="meta_description" name="meta_description" rows="3" maxlength="320"
                          class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $post->meta_description) }}</textarea>
                @error('meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">The text under your link in Google. Aim for 120 to 160 characters.</div>
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
                <div class="form-text">
                    Lowercase letters, numbers and dashes. Empty = made from the title.
                    Changing the address of a live article is safe: the old address redirects to the new one.
                </div>
            </div>

            {{-- The hidden 0 makes sure "unchecked" is sent too --}}
            <input type="hidden" name="noindex" value="0">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="noindex" name="noindex" value="1"
                       @checked(old('noindex', $post->noindex))>
                <label class="form-check-label" for="noindex">Hide this article from search engines (noindex)</label>
                <div class="form-text">Visitors can still open it. It is left out of Google and the sitemap.</div>
            </div>
        </div>
    </div>

    {{-- ============ RIGHT: PUBLISH, SEO SCORE, ORGANISE, PHOTO ============ --}}
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

            <div class="mb-0">
                <label for="published_at" class="form-label">Publish date and time</label>
                <input type="datetime-local" id="published_at" name="published_at" value="{{ $dateValue }}"
                       class="form-control @error('published_at') is-invalid @enderror">
                @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">
                    Times are in {{ $tz }}. Empty = publish now. A future date schedules the post:
                    it goes live at that time. The Save button is at the end of the page.
                </div>
            </div>
        </div>

        {{-- SEO SCORE: a live checklist --}}
        <div class="sgh-panel mb-3">
            <div class="sgh-panel-head"><div><h2>SEO score</h2><p>A writing guide, not a ranking promise.</p></div></div>

            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="sgh-ring" id="scoreRing" style="--pct: 0" role="img" aria-label="SEO score">
                    <span id="scoreNum">0</span>
                </div>
                <div class="small text-secondary" id="scoreText"></div>
            </div>

            <ul class="sgh-checks" id="scoreList" aria-live="polite"></ul>
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
            <div class="sgh-panel-head"><div><h2>Featured image</h2><p>Shown at the top of the article. JPG, PNG or WebP, up to 2 MB.</p></div></div>

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

{{-- SAVE BAR: at the very end of the page --}}
<div class="sgh-form-actions">
    <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
    <button type="submit" class="btn btn-primary rounded-pill px-5">
        <i class="bi bi-check2 me-1"></i> Save post
    </button>
</div>

{{-- INSERT IMAGE DIALOG. Its boxes have no "name", so they are never sent with the post. --}}
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="imageModalLabel">Insert image</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="sgh-upload-preview mb-3" id="imgPreview"><i class="bi bi-image" aria-hidden="true"></i></div>

                <label for="imgFile" class="form-label">Photo <span class="text-secondary">(JPG, PNG or WebP, up to 2 MB)</span></label>
                <input type="file" id="imgFile" class="form-control mb-3" accept="image/jpeg,image/png,image/webp">

                <label for="imgAlt" class="form-label">Describe the photo <span class="text-danger">*</span></label>
                <input type="text" id="imgAlt" class="form-control mb-3" maxlength="160"
                       placeholder="A round mirror above a wooden console table">

                <label for="imgCaption" class="form-label">Caption <span class="text-secondary">(optional, shown under the photo)</span></label>
                <input type="text" id="imgCaption" class="form-control mb-3" maxlength="200">

                <label for="imgAlign" class="form-label">Position</label>
                <select id="imgAlign" class="form-select">
                    <option value="full">Full width</option>
                    <option value="center">Centred</option>
                    <option value="left">Left, text wraps around it</option>
                    <option value="right">Right, text wraps around it</option>
                </select>

                <p class="text-danger small mt-3 mb-0" id="imgError" role="alert"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="imgInsert">Insert image</button>
            </div>
        </div>
    </div>
</div>

{{-- The editor: toolbar, image dialog, paste cleaner, preview, tags, Google preview and SEO score --}}
<script>
    (function () {
        function $(id) { return document.getElementById(id); }

        var cfg   = $('seoConfig').dataset;
        var ta    = $('content');
        var words = $('wordCount');

        function squish(s) { return s.replace(/\s+/g, ' ').trim(); }
        function esc(s) { return s.replace(/[.*+?^$()|[\]\\{}]/g, '\\$&'); }

        // ----- word count and reading time -----
        function count() {
            var n = ta.value.trim().split(/\s+/).filter(Boolean).length;
            words.textContent = n + ' words \u00b7 ' + Math.max(1, Math.ceil(n / 200)) + ' min read';
        }
        ta.addEventListener('input', count);
        count();

        // ----- toolbar: puts Markdown around the selected text -----
        function wrap(before, after, placeholder) {
            var s = ta.selectionStart, e = ta.selectionEnd;
            ta.setRangeText(before + (ta.value.slice(s, e) || placeholder) + after, s, e, 'select');
            ta.focus(); count(); analyse();
        }
        function linePrefix(prefix) {
            var start = ta.value.lastIndexOf('\n', ta.selectionStart - 1) + 1;
            ta.setRangeText(prefix, start, start, 'end');
            ta.focus(); count(); analyse();
        }
        // H2 / H3: marks the current line as a heading, or removes the mark if it already is one
        function setHeading(level) {
            var value  = ta.value;
            var start  = value.lastIndexOf('\n', ta.selectionStart - 1) + 1;
            var endAt  = value.indexOf('\n', ta.selectionStart);
            var end    = endAt === -1 ? value.length : endAt;
            var line   = value.slice(start, end);
            var found  = line.match(/^(#{1,6})\s+/);
            var clean  = line.replace(/^#{1,6}\s+/, '');
            var prefix = new Array(level + 1).join('#') + ' ';
            var next   = (found && found[1].length === level) ? clean : prefix + clean;
            ta.setRangeText(next, start, end, 'end');
            ta.focus(); count(); analyse();
        }

        var actions = {
            bold:   function () { wrap('**', '**', 'bold text'); },
            italic: function () { wrap('*', '*', 'italic text'); },
            h2:     function () { setHeading(2); },
            h3:     function () { setHeading(3); },
            link:   function () { wrap('[', '](https://)', 'link text'); },
            image:  function () { openImageDialog(); },
            list:   function () { linePrefix('- '); },
            quote:  function () { linePrefix('> '); }
        };
        document.querySelectorAll('[data-md]').forEach(function (b) {
            b.addEventListener('click', function () { actions[b.dataset.md](); });
        });

        // ----- Write / Preview -----
        var paneWrite = $('paneWrite'), panePreview = $('panePreview');
        var tabWrite = $('tabWrite'), tabPreview = $('tabPreview');

        tabWrite.addEventListener('click', function () {
            paneWrite.hidden = false; panePreview.hidden = true;
            tabWrite.classList.add('active'); tabPreview.classList.remove('active');
        });

        tabPreview.addEventListener('click', async function () {
            paneWrite.hidden = true; panePreview.hidden = false;
            tabPreview.classList.add('active'); tabWrite.classList.remove('active');
            panePreview.innerHTML = '<p class="text-secondary">Loading preview\u2026</p>';

            try {
                // The server turns the Markdown into safe HTML (raw HTML is removed there)
                var res = await fetch(cfg.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf
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

        // =====================================================
        // INSERT IMAGE (dialog). The picture is uploaded first, then its Markdown is inserted.
        // =====================================================
        var modalEl = $('imageModal'), modal = null;     // Bootstrap loads after this script, so the dialog is made on first use
        var imgFile = $('imgFile'), imgAlt = $('imgAlt'), imgCaption = $('imgCaption'), imgAlign = $('imgAlign');
        var imgPreview = $('imgPreview'), imgError = $('imgError'), imgInsert = $('imgInsert');
        var savedStart = 0, savedEnd = 0, previewUrl = null;
        var okTypes = ['image/jpeg', 'image/png', 'image/webp'];

        function resetDialog() {
            imgFile.value = ''; imgAlt.value = ''; imgCaption.value = ''; imgAlign.value = 'full';
            imgError.textContent = '';
            imgInsert.disabled = false;
            if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
            imgPreview.innerHTML = '<i class="bi bi-image" aria-hidden="true"></i>';
        }
        function openImageDialog() {
            savedStart = ta.selectionStart; savedEnd = ta.selectionEnd;
            resetDialog();
            if (!modal) { modal = new bootstrap.Modal(modalEl); }
            modal.show();
        }

        imgFile.addEventListener('change', function () {
            imgError.textContent = '';
            var file = imgFile.files[0];
            if (!file) { return; }
            if (okTypes.indexOf(file.type) === -1) {
                imgError.textContent = 'The photo must be a JPG, PNG or WebP file.';
                imgFile.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                imgError.textContent = 'The photo must be smaller than 2 MB.';
                imgFile.value = '';
                return;
            }
            if (previewUrl) { URL.revokeObjectURL(previewUrl); }
            previewUrl = URL.createObjectURL(file);
            imgPreview.innerHTML = '<img src="' + previewUrl + '" alt="Preview of the chosen photo">';
        });

        imgInsert.addEventListener('click', async function () {
            var file = imgFile.files[0];
            var alt  = squish(imgAlt.value).replace(/[\[\]\\]/g, '');
            if (!file) { imgError.textContent = 'Please choose a photo.'; return; }
            if (!alt)  { imgError.textContent = 'Please describe the photo. Screen readers and search engines use it.'; return; }

            imgError.textContent = '';
            imgInsert.disabled = true;

            try {
                var fd = new FormData();
                fd.append('image', file);
                var res = await fetch(cfg.uploadUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
                    body: fd
                });

                if (res.status === 422) {
                    var bad = await res.json();
                    var first = bad.errors && bad.errors.image ? bad.errors.image[0] : 'The photo could not be used.';
                    throw new Error(first);
                }
                if (!res.ok) { throw new Error('The upload failed. Please try again.'); }

                var data = await res.json();
                var url  = data.url + (imgAlign.value !== 'full' ? '#' + imgAlign.value : '');
                var cap  = squish(imgCaption.value).replace(/\\/g, '').replace(/"/g, "'");
                var md   = '\n\n![' + alt + '](' + url + (cap ? ' "' + cap + '"' : '') + ')\n\n';

                ta.setRangeText(md, savedStart, savedEnd, 'end');
                modal.hide();
                ta.focus(); count(); analyse();
            } catch (err) {
                imgError.textContent = err.message || 'The upload failed. Please try again.';
                imgInsert.disabled = false;
            }
        });

        // Enter inside the dialog must not save the whole post
        [imgAlt, imgCaption].forEach(function (el) {
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); imgInsert.click(); }
            });
        });

        // =====================================================
        // PASTE CLEANER: formatted text from Word, Google Docs or a web page becomes Markdown.
        // Plain text is pasted as it is. The server removes raw HTML again when the post is saved.
        // =====================================================
        var SKIP = { SCRIPT: 1, STYLE: 1, META: 1, LINK: 1, NOSCRIPT: 1, IFRAME: 1, OBJECT: 1, EMBED: 1, SVG: 1, FORM: 1,
                     INPUT: 1, BUTTON: 1, SELECT: 1, TEXTAREA: 1, HEAD: 1, TITLE: 1, CANVAS: 1, VIDEO: 1, AUDIO: 1 };
        var BLOCK = { P: 1, DIV: 1, H1: 1, H2: 1, H3: 1, H4: 1, H5: 1, H6: 1, UL: 1, OL: 1, LI: 1, BLOCKQUOTE: 1, HR: 1,
                      TABLE: 1, TBODY: 1, THEAD: 1, TFOOT: 1, TR: 1, SECTION: 1, ARTICLE: 1, HEADER: 1, FOOTER: 1,
                      MAIN: 1, ASIDE: 1, NAV: 1, FIGURE: 1, FIGCAPTION: 1, PRE: 1, DL: 1, DT: 1, DD: 1 };

        function safeUrl(u) {
            u = (u || '').trim();
            if (!/^(https?:\/\/|\/(?!\/)|mailto:)/i.test(u)) { return ''; }
            return u.replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\s/g, '%20');
        }
        function weightOf(el) { return (el.style && el.style.fontWeight) || ''; }
        function isBold(el) {
            var T = el.tagName.toUpperCase(), w = weightOf(el);
            if (w === 'bold' || w === 'bolder' || parseInt(w, 10) >= 600) { return true; }
            // Google Docs wraps everything in <b style="font-weight:normal">: that is NOT bold
            return T === 'STRONG' || (T === 'B' && w !== 'normal' && w !== '400');
        }
        function isItalic(el) {
            var T = el.tagName.toUpperCase();
            return T === 'EM' || T === 'I' || (el.style && el.style.fontStyle === 'italic');
        }

        function inlineNode(n) {
            if (n.nodeType === 3) { return n.nodeValue.replace(/\s+/g, ' '); }
            if (n.nodeType !== 1) { return ''; }
            var T = n.tagName.toUpperCase();
            if (SKIP[T]) { return ''; }
            if (T === 'BR') { return '\\\n'; }
            if (T === 'IMG') {
                var src = safeUrl(n.getAttribute('src'));
                if (!src) { return ''; }                       // pictures pasted as data are dropped
                return '![' + (n.getAttribute('alt') || '').replace(/[\[\]\\]/g, '').trim() + '](' + src + ')';
            }
            var inner = '';
            n.childNodes.forEach(function (c) { inner += inlineNode(c); });
            var core = inner.trim();
            if (!core) { return inner; }
            var lead = inner.match(/^\s*/)[0], trail = inner.match(/\s*$/)[0];
            if (T === 'A') {
                var href = safeUrl(n.getAttribute('href'));
                if (href) { core = '[' + core + '](' + href + ')'; }
            }
            if (isBold(n))   { core = '**' + core + '**'; }
            if (isItalic(n)) { core = '*' + core + '*'; }
            return lead + core + trail;
        }

        function blocks(parent) {
            var out = [], buf = '';
            function flush() {
                var t = buf.replace(/[ \t]+/g, ' ').replace(/\n[ \t]+/g, '\n').trim();
                if (t) { out.push(t); }
                buf = '';
            }
            parent.childNodes.forEach(function (c) {
                if (c.nodeType === 1 && BLOCK[c.tagName.toUpperCase()]) {
                    flush();
                    var b = blockEl(c);
                    if (b) { out.push(b); }
                } else {
                    buf += inlineNode(c);
                }
            });
            flush();
            return out.join('\n\n');
        }

        function blockEl(el) {
            var T = el.tagName.toUpperCase();
            if (SKIP[T]) { return ''; }

            var h = /^H([1-6])$/.exec(T);
            var wordTitle = T === 'P' && /(^|\s)MsoTitle(\s|$)/.test(el.className || '');
            if (h || wordTitle) {
                var level = h ? Math.min(parseInt(h[1], 10), 3) : 1;
                var text = '';
                el.childNodes.forEach(function (c) { text += inlineNode(c); });
                text = squish(text).replace(/\*\*/g, '');
                return text ? '######'.slice(0, level) + ' ' + text : '';
            }
            if (T === 'HR') { return '---'; }
            if (T === 'UL' || T === 'OL') { return list(el, T === 'OL'); }
            if (T === 'BLOCKQUOTE') {
                var q = blocks(el);
                return q ? q.split('\n').map(function (l) { return '> ' + l; }).join('\n') : '';
            }
            if (T === 'PRE') {
                var code = (el.textContent || '').replace(/\s+$/, '');
                return code ? '```\n' + code + '\n```' : '';
            }
            if (T === 'TR') {
                var cells = [];
                el.childNodes.forEach(function (c) {
                    if (c.nodeType === 1 && /^(TD|TH)$/i.test(c.tagName)) {
                        var t = blocks(c).replace(/\n+/g, ' ').trim();
                        if (t) { cells.push(t); }
                    }
                });
                return cells.join(' | ');
            }
            return blocks(el);   // p, div, section, table, figure ... : their contents
        }

        function list(el, ordered) {
            var lines = [], n = 1, pad = ordered ? '   ' : '  ';
            el.childNodes.forEach(function (li) {
                if (li.nodeType !== 1 || li.tagName.toUpperCase() !== 'LI') { return; }
                var text = '', nested = [];
                li.childNodes.forEach(function (c) {
                    var T = c.nodeType === 1 ? c.tagName.toUpperCase() : '';
                    if (T === 'UL' || T === 'OL') { nested.push(c); }
                    else if (BLOCK[T]) { text += ' ' + blockEl(c).replace(/\n+/g, ' '); }
                    else { text += inlineNode(c); }
                });
                text = squish(text);
                if (text) { lines.push((ordered ? (n++) + '. ' : '- ') + text); }
                nested.forEach(function (sub) {
                    var inner = list(sub, sub.tagName.toUpperCase() === 'OL');
                    if (inner) { lines.push(inner.split('\n').map(function (l) { return pad + l; }).join('\n')); }
                });
            });
            return lines.join('\n');
        }

        function htmlToMarkdown(html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');   // scripts in it never run
            return blocks(doc.body).replace(/\n{3,}/g, '\n\n').trim();
        }
        // Only formatted content is converted. Plain text, or code copied from an editor, pastes normally.
        function hasStructure(html) {
            return /<(h[1-6]|strong|b|em|i|a|ul|ol|li|blockquote|img|table|pre)[\s>]/i.test(html)
                || /font-weight:\s*(bold|[6-9]00)/i.test(html)
                || /font-style:\s*italic/i.test(html);
        }

        var noteTimer;
        function note(text) {
            var el = $('pasteNote');
            el.textContent = text;
            clearTimeout(noteTimer);
            noteTimer = setTimeout(function () { el.textContent = ''; }, 7000);
        }

        ta.addEventListener('paste', function (e) {
            var cd = e.clipboardData;
            if (!cd) { return; }
            var html = cd.getData('text/html');
            if (!html || !hasStructure(html)) { return; }

            var md = htmlToMarkdown(html);
            if (!md) { return; }

            e.preventDefault();
            var s = ta.selectionStart, end = ta.selectionEnd;
            var before = ta.value.slice(0, s), after = ta.value.slice(end);

            // A block of text starts on its own line, not in the middle of another one
            if (/\n|^(#{1,3} |- |\d+\. |> |---)/.test(md)) {
                if (before && !/\n\n$/.test(before)) { md = (/\n$/.test(before) ? '\n' : '\n\n') + md; }
                if (after && !/^\n/.test(after)) { md = md + '\n\n'; }
            }

            ta.setRangeText(md, s, end, 'end');
            count(); analyse();
            note(ta.value.length > 60000
                ? 'The article is now longer than the 60,000 character limit. Please shorten it before saving.'
                : 'Formatting kept: headings, bold, lists and links were converted. Please check the result.');
        });

        // ----- quick-add tag buttons -----
        var tagBox = $('tags');
        document.querySelectorAll('[data-add-tag]').forEach(function (b) {
            b.addEventListener('click', function () {
                var list = tagBox.value.split(',').map(function (t) { return t.trim(); }).filter(Boolean);
                var exists = list.some(function (t) { return t.toLowerCase() === b.dataset.addTag.toLowerCase(); });
                if (!exists) { list.push(b.dataset.addTag); }
                tagBox.value = list.join(', ');
            });
        });

        // ----- featured photo preview (the server checks the file again) -----
        var img = $('featured_image'), box = $('imagePreview');
        img.addEventListener('change', function () {
            var file = img.files[0];
            if (!file) { analyse(); return; }
            if (file.size > 2 * 1024 * 1024) {
                alert('The photo must be smaller than 2 MB.');
                img.value = '';
                analyse();
                return;
            }
            box.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="Preview of the chosen photo">';
            analyse();
        });

        // =====================================================
        // GOOGLE PREVIEW + SEO SCORE (live)
        // Everything is written with textContent, so typed text can never run as HTML.
        // The saved score on the SEO audit page is worked out by PHP and is the authoritative one.
        // =====================================================
        var hadImage = cfg.hadImage === '1';
        var nodes = {
            title: $('title'), excerpt: $('excerpt'), slug: $('slug'), seoTitle: $('seo_title'),
            meta: $('meta_description'), kw: $('focus_keyword'), alt: $('featured_image_alt'),
            remove: $('remove_image')
        };

        function slugify(s) {
            return s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        }
        function plainText(md) {
            return squish(md.replace(/```[\s\S]*?```/g, ' ')
                            .replace(/!\[[^\]]*\]\([^)]*\)/g, ' ')
                            .replace(/\[([^\]]*)\]\([^)]*\)/g, '$1')
                            .replace(/[#>*_`~]/g, ' ')
                            .replace(/^\s*-\s+/gm, ' '));
        }
        function cut(s, max) { return s.length > max ? s.slice(0, max - 1).trim() + '\u2026' : s; }
        function lenClass(n, min, max) { return n >= min && n <= max ? 'ok' : 'warn'; }

        // A link to another page of this site: "](/shop)" or "](https://your-domain/shop)"
        var internalLink = new RegExp('\\]\\((?:/(?!/)|' + esc(cfg.site) + ')[^)\\s]*\\)', 'i');

        function analyse() {
            var kw       = squish(nodes.kw.value);
            var titleTag = squish(nodes.seoTitle.value) || squish(nodes.title.value);
            var meta     = squish(nodes.meta.value);
            var slug     = squish(nodes.slug.value) || slugify(nodes.title.value);
            var plain    = plainText(ta.value);
            var tokens   = plain ? plain.split(' ') : [];
            var intro    = tokens.slice(0, 100).join(' ');
            var headings = (ta.value.match(/^#{1,3}\s+\S/gm) || []).length;
            var kwSlug   = slugify(kw);
            var hasPhoto = (hadImage && !(nodes.remove && nodes.remove.checked)) || img.files.length > 0;
            var noPics   = ta.value.replace(/!\[[^\]]*\]\([^)]*\)/g, '');   // pictures are not links

            function has(text) { return kw !== '' && text.toLowerCase().indexOf(kw.toLowerCase()) !== -1; }
            var needKw = 'Set a focus keyword first.';

            // ----- Google preview -----
            $('serpUrl').textContent   = cfg.blogUrl.replace(/^https?:\/\//, '').replace(/\//g, ' \u203a ') + ' \u203a ' + (slug || 'your-article');
            $('serpTitle').textContent = cut(titleTag || 'Your article title', 60);
            $('serpDesc').textContent  = cut(meta || squish(nodes.excerpt.value) || plain || 'Your description appears here.', 160);

            // ----- counters -----
            var lt = $('lenTitle'), lm = $('lenMeta');
            lt.textContent = titleTag.length + ' / 60'; lt.className = 'sgh-len ' + lenClass(titleTag.length, 30, 60);
            lm.textContent = meta.length + ' / 160';    lm.className = 'sgh-len ' + lenClass(meta.length, 120, 160);

            // ----- score (the same eleven checks, with the same points, as SeoScore.php) -----
            var checks = [
                ['Focus keyword is set', 10, kw !== '', 'Type the word or short phrase this article is about.'],
                ['Keyword is in the title', 10, has(titleTag), kw === '' ? needKw : 'Use the keyword in the title.'],
                ['Title is 30 to 60 characters', 10, titleTag.length >= 30 && titleTag.length <= 60, 'The title is ' + titleTag.length + ' characters. Aim for 30 to 60.'],
                ['Meta description is 120 to 160 characters', 10, meta.length >= 120 && meta.length <= 160, 'The description is ' + meta.length + ' characters. Aim for 120 to 160.'],
                ['Keyword is in the meta description', 10, has(meta), kw === '' ? needKw : 'Use the keyword in the meta description.'],
                ['Keyword is in the first 100 words', 10, has(intro), kw === '' ? needKw : 'Mention the keyword near the start of the article.'],
                ['Article is at least 600 words', 10, tokens.length >= 600, 'The article has ' + tokens.length + ' words. Longer, useful articles tend to do better.'],
                ['Has at least 2 subheadings', 5, headings >= 2, 'Break the article up with ## subheadings.'],
                ['Featured photo has a description', 10, hasPhoto && squish(nodes.alt.value) !== '', 'Add a featured photo and describe it in a few words.'],
                ['Keyword is in the web address', 5, kwSlug !== '' && slug.indexOf(kwSlug) !== -1, kw === '' ? needKw : 'Put the keyword in the slug, for example ' + kwSlug + '-ideas.'],
                ['Links to another page on your site', 10, internalLink.test(noPics), 'Link to a product or another article, for example [our mirrors](/shop/mirrors).']
            ];

            var score = 0, listEl = $('scoreList');
            listEl.textContent = '';
            checks.forEach(function (c) {
                if (c[2]) { score += c[1]; }
                var li = document.createElement('li');
                li.className = c[2] ? 'is-ok' : 'is-bad';
                var icon = document.createElement('i');
                icon.className = 'bi ' + (c[2] ? 'bi-check-circle-fill' : 'bi-x-circle-fill');
                var span = document.createElement('span');
                span.textContent = c[0];
                li.appendChild(icon); li.appendChild(span);
                if (!c[2]) {
                    var tip = document.createElement('small');
                    tip.textContent = c[3];
                    li.appendChild(tip);
                }
                listEl.appendChild(li);
            });

            var ring = $('scoreRing');
            ring.style.setProperty('--pct', score);
            ring.className = 'sgh-ring ' + (score >= 80 ? 'good' : (score >= 50 ? 'ok' : 'bad'));
            $('scoreNum').textContent = score;
            $('scoreText').textContent = score >= 80 ? 'Looking good.' : (score >= 50 ? 'Getting there. Fix the red items.' : 'Needs work. Start with the red items.');
        }

        // Re-check shortly after typing stops
        var timer;
        function later() { clearTimeout(timer); timer = setTimeout(analyse, 200); }
        ['title', 'excerpt', 'content', 'slug', 'seo_title', 'meta_description', 'focus_keyword', 'featured_image_alt', 'remove_image']
            .forEach(function (id) {
                var el = $(id);
                if (el) { el.addEventListener('input', later); el.addEventListener('change', later); }
            });

        analyse();
    })();
</script>