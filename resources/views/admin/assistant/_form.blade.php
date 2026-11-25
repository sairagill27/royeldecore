{{-- SHARED FORM for Add and Edit. old('x', $answer->x) = "what you typed after an error, otherwise the saved value". --}}

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> Please fix the highlighted fields and try again.
    </div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="sgh-panel mb-3">
            <div class="mb-3">
                <label for="question" class="form-label">Question</label>
                <input type="text" id="question" name="question" value="{{ old('question', $answer->question) }}" maxlength="80" required
                       class="form-control @error('question') is-invalid @enderror" placeholder="How does cash on delivery work?">
                @error('question') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Up to 80 characters. It also appears as a button, and clicking the button always shows this answer.</div>
            </div>

            <div class="mb-3">
                <label for="keywords" class="form-label">Words that trigger it</label>
                <textarea id="keywords" name="keywords" rows="3" maxlength="400" required
                          class="form-control @error('keywords') is-invalid @enderror"
                          placeholder="cash on delivery, cod, pay when it arrives">{{ old('keywords', $answer->keywords) }}</textarea>
                @error('keywords') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">
                    Words or phrases that visitors might type, <strong>separated by commas</strong>.
                    Whole words only, and capitals do not matter. A longer phrase wins over a single word.
                    Avoid very common words, such as "order" or "price", because they would catch too many questions.
                </div>
            </div>

            <div>
                <label for="answer" class="form-label">Answer</label>
                <textarea id="answer" name="answer" rows="6" maxlength="800" required
                          class="form-control @error('answer') is-invalid @enderror">{{ old('answer', $answer->answer) }}</textarea>
                @error('answer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">
                    Plain text, up to 800 characters, no links. A new line starts a new paragraph.
                    <strong>Only write what is true and agreed.</strong> Do not promise delivery times, returns or discounts unless your shop has decided them.
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="sgh-panel">
            <div class="sgh-panel-head"><div><h2>Status</h2></div></div>

            {{-- The hidden 0 makes sure "unticked" is sent too --}}
            <input type="hidden" name="is_active" value="0">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $answer->is_active))>
                <label class="form-check-label" for="is_active">Use this answer</label>
                <div class="form-text">Untick to hide it without deleting it.</div>
            </div>
        </div>
    </div>
</div>

<div class="sgh-form-actions">
    <a href="{{ route('admin.assistant-answers.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
    <button type="submit" class="btn btn-primary rounded-pill px-5"><i class="bi bi-check2 me-1"></i> Save answer</button>
</div>