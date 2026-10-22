{{-- COMMENTS under an article. Needs $post. Only approved comments are shown.
     Everything typed by visitors is printed with {{ }} or e(), so it is always plain text. --}}
@php
$comments = $post->comments()->approved()->oldest()->get(['id', 'name', 'body', 'created_at']);
$me = auth()->user();
@endphp

<section class="sgh-section pt-0" id="comments">
    <div class="container">
        <div class="sgh-comments">

            <h2 class="h4 mb-3">Comments ({{ $comments->count() }})</h2>

            @forelse ($comments as $comment)
            <div class="sgh-comment">
                <span class="sgh-comment-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($comment->name, 0, 1)) }}</span>
                <div class="sgh-comment-body">
                    <p class="mb-1">
                        <strong>{{ $comment->name }}</strong>
                        <small class="text-secondary ms-2">{{ $comment->created_at->timezone(config('shop.timezone'))->format('d M Y') }}</small>
                    </p>
                    <p class="mb-0">{!! nl2br(e($comment->body)) !!}</p>
                </div>
            </div>
            @empty
            <p class="text-secondary">No comments yet. Be the first to share your thoughts.</p>
            @endforelse

            {{-- ============ THE FORM ============ --}}
            <div class="sgh-comment-form mt-4">
                <h3 class="h5 mb-3">Leave a comment</h3>

                {{-- @csrf = security token --}}
                <form action="{{ route('blog.comments.store', $post->slug) }}" method="POST" novalidate>
                    @csrf

                    {{-- SPAM TRAP: people never see this field. If it is filled in, a bot did it. --}}
                    <div class="sgh-hp" aria-hidden="true">
                        <label for="cm-website">Leave this field empty</label>
                        <input type="text" id="cm-website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    @if ($me)
                    <p class="small text-secondary">Commenting as <strong>{{ $me->name }}</strong>.</p>
                    @else
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="cm-name" class="form-label">Name</label>
                            <input type="text" id="cm-name" name="name" value="{{ old('name') }}" maxlength="80"
                                class="form-control @error('name') is-invalid @enderror" autocomplete="name" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="cm-email" class="form-label">Email <span class="text-secondary">(never shown)</span></label>
                            <input type="email" id="cm-email" name="email" value="{{ old('email') }}" maxlength="255"
                                class="form-control @error('email') is-invalid @enderror" autocomplete="email" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="cm-body" class="form-label">Comment</label>
                        <textarea id="cm-body" name="body" rows="4" maxlength="1500"
                            class="form-control @error('body') is-invalid @enderror" required>{{ old('body') }}</textarea>
                        @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Comments are checked before they appear. Please be kind, and include at most one link.</div>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill px-4">Post comment</button>
                </form>
            </div>
        </div>
    </div>
</section>