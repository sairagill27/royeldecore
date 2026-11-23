@extends('layouts.admin')

@section('title', 'Page pictures')
@section('heading', 'Page pictures')

@section('content')

<div class="sgh-panel mb-3">
    <div class="sgh-panel-head">
        <div>
            <h2>Header pictures</h2>
            <p>A decoration photo for the green header of each page. It sits on the right and fades into the green.</p>
        </div>
    </div>
    <ul class="small text-secondary mb-0 ps-3">
        <li>Choose a <strong>wide, landscape</strong> picture. It is cropped to {{ $width }} x {{ $height }} pixels, at least 800 x 350, up to {{ round($max_kb / 1024) }} MB.</li>
        <li>Put the interesting part on the <strong>right</strong>, and keep the left calm. The header's text sits on the left.</li>
        <li>Use <strong>your own photos</strong>, or pictures you are licensed to use. Avoid readable brand names, logos and people's faces.</li>
        <li>Without a picture, the header stays plain green with a soft golden glow.</li>
    </ul>
</div>

<div class="row g-3">
    @foreach ($slots as $slot)
        <div class="col-md-6 col-xl-4">
            <div class="sgh-panel h-100">
                {{-- {{ }} escapes the text (safe) --}}
                <h3 class="h5 mb-1">{{ $slot['label'] }}</h3>
                <p class="small text-secondary">{{ $slot['hint'] }}</p>

                <div class="rounded-3 overflow-hidden border bg-light mb-3" style="aspect-ratio: 16 / 7;">
                    @if ($slot['has'])
                        <img src="{{ $slot['url'] }}" alt="Current picture for the {{ $slot['label'] }}" class="w-100 h-100 object-fit-cover" loading="lazy">
                    @else
                        <div class="d-flex h-100 align-items-center justify-content-center text-secondary small">No picture yet</div>
                    @endif
                </div>

                {{-- @csrf = security token. enctype="multipart/form-data" is REQUIRED for picture upload. --}}
                <form action="{{ route('admin.page-pictures.update', $slot['key']) }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    <label for="pic-{{ $slot['key'] }}" class="form-label small mb-1">Choose a picture</label>
                    <input type="file" id="pic-{{ $slot['key'] }}" name="picture" accept="image/jpeg,image/png,image/webp"
                           class="form-control form-control-sm @error('picture', 'slot-' . $slot['key']) is-invalid @enderror">
                    @error('picture', 'slot-' . $slot['key']) <div class="invalid-feedback">{{ $message }}</div> @enderror

                    <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                        <label for="focus-{{ $slot['key'] }}" class="visually-hidden">Which part to keep when cropping</label>
                        <select id="focus-{{ $slot['key'] }}" name="focus" class="form-select form-select-sm w-auto">
                            <option value="center">Keep the middle</option>
                            <option value="right" selected>Keep the right side</option>
                            <option value="left">Keep the left side</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
                            {{ $slot['has'] ? 'Replace' : 'Save picture' }}
                        </button>
                    </div>
                    @if ($slot['has'])
                        <p class="small text-secondary mt-2 mb-0">Saved, {{ $slot['kb'] }} KB.</p>
                    @endif
                </form>

                @if ($slot['has'])
                    <form action="{{ route('admin.page-pictures.destroy', $slot['key']) }}" method="POST" class="mt-2"
                          onsubmit="return confirm('Remove this picture?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-link btn-sm text-danger p-0">Remove picture</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
</div>

@endsection