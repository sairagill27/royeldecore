@extends('layouts.admin')

@section('title', 'Affiliate links')
@section('heading', 'Affiliate links')

@section('content')

<div class="sgh-panel">

    {{-- Status tabs, with counters --}}
    <nav class="sgh-pills mb-3" aria-label="Filter links by status">
        <a href="{{ route('admin.affiliate-links.index', array_filter(['q' => $q, 'section' => $section])) }}"
           class="sgh-pill {{ $state ? '' : 'active' }}">All ({{ $counts['all'] }})</a>
        @foreach (['active' => 'Active', 'paused' => 'Paused', 'expired' => 'Expired'] as $key => $label)
            <a href="{{ route('admin.affiliate-links.index', array_filter(['state' => $key, 'q' => $q, 'section' => $section])) }}"
               class="sgh-pill {{ $state === $key ? 'active' : '' }}">{{ $label }} ({{ $counts[$key] }})</a>
        @endforeach
    </nav>

    <div class="sgh-admin-toolbar">
        <form action="{{ route('admin.affiliate-links.index') }}" method="GET" class="sgh-admin-filters" role="search">
            @if ($state) <input type="hidden" name="state" value="{{ $state }}"> @endif

            <label for="q" class="visually-hidden">Search links</label>
            <input type="search" id="q" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Search name or store...">

            <label for="section" class="visually-hidden">Section</label>
            <select name="section" id="section" class="form-select form-select-sm">
                <option value="">All sections</option>
                @foreach ($sections as $name)
                    <option value="{{ $name }}" @selected($section === $name)>{{ $name }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Filter</button>
            @if ($q !== '' || $section !== '' || $state)
                <a href="{{ route('admin.affiliate-links.index') }}" class="btn btn-link btn-sm">Reset</a>
            @endif
        </form>

        <div class="d-flex flex-wrap gap-2 ms-lg-auto">
            <a href="{{ route('admin.affiliate-analytics') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-graph-up-arrow me-1"></i> Analytics
            </a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.activity') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-clock-history me-1"></i> Activity log
                </a>
            @endif
            <a href="{{ route('recommended') }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-box-arrow-up-right me-1"></i> Recommended page
            </a>
            <a href="{{ route('admin.affiliate-links.create') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                <i class="bi bi-plus-lg me-1"></i> Add a link
            </a>
        </div>
    </div>

    <p class="small text-secondary">
        Put <code>[[affiliate:short-name]]</code> on its own line in a blog article to show a link as a card.
    </p>

    @if ($links->isEmpty())

        <p class="sgh-panel-empty"><i class="bi bi-link-45deg"></i> No links found. Click "Add a link" after your affiliate program approves you.</p>

    @else
        <div class="table-responsive">
            <table class="table sgh-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Link</th>
                        <th>Shortcode</th>
                        <th class="text-center">Clicks</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($links as $link)
                        @php $linkState = $link->state(); @endphp
                        <tr>
                            <td>
                                {{-- {{ }} escapes the text (safe) --}}
                                <a href="{{ route('admin.affiliate-links.edit', $link) }}" class="fw-semibold text-decoration-none">{{ $link->name }}</a>
                                @if ($link->is_featured) <i class="bi bi-star-fill text-warning ms-1" title="Featured" aria-label="Featured"></i> @endif
                                <small class="d-block text-secondary">
                                    {{ $link->merchant }}@if ($link->section) &middot; {{ $link->section }}@endif
                                    @if ($link->expires_at) &middot; ends {{ $link->expires_at->format('d M Y') }}@endif
                                </small>
                            </td>
                            <td class="text-nowrap">
                                <code>[[affiliate:{{ $link->slug }}]]</code>
                                <button type="button" class="btn btn-outline-secondary btn-sm ms-1" data-copy="[[affiliate:{{ $link->slug }}]]">Copy</button>
                            </td>
                            <td class="text-center text-nowrap">
                                <strong>{{ number_format($link->clicks_total) }}</strong>
                                <small class="d-block text-secondary">{{ number_format((int) ($recent[$link->id] ?? 0)) }} in 30 days</small>
                            </td>
                            <td>
                                @if ($linkState === 'active')
                                    <span class="badge sgh-badge-ok">Active</span>
                                @elseif ($linkState === 'paused')
                                    <span class="badge sgh-badge-draft">Paused</span>
                                @else
                                    <span class="badge sgh-badge-out">Expired</span>
                                @endif
                                @if ($link->is_direct)
                                    <span class="badge sgh-badge-low" title="Goes straight to the store, clicks are not counted here">Direct</span>
                                @endif
                                @if ($link->needsCheck())
                                    <span class="badge sgh-badge-low" title="Nobody has opened and checked this link lately">Check link</span>
                                @endif
                            </td>
                            <td>
                                <div class="sgh-row-actions">
                                    <a href="{{ route('admin.affiliate-links.edit', $link) }}" class="sgh-icon-action"
                                       title="Edit" aria-label="Edit {{ $link->name }}"><i class="bi bi-pencil"></i></a>

                                    {{-- @csrf = security token. @method('PATCH') tells Laravel "this changes it". --}}
                                    <form action="{{ route('admin.affiliate-links.checked', $link) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="sgh-icon-action" title="I opened this link and it works"
                                                aria-label="Mark {{ $link->name }} as checked"><i class="bi bi-check2-circle"></i></button>
                                    </form>

                                    <form action="{{ route('admin.affiliate-links.toggle', $link) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="sgh-icon-action"
                                                title="{{ $linkState === 'active' ? 'Pause (hide from visitors)' : 'Show again' }}"
                                                aria-label="{{ $linkState === 'active' ? 'Pause' : 'Resume' }} {{ $link->name }}">
                                            <i class="bi {{ $linkState === 'active' ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                        </button>
                                    </form>

                                    {{-- Delete: admins only --}}
                                    @if (auth()->user()->isAdmin())
                                        <form action="{{ route('admin.affiliate-links.destroy', $link) }}" method="POST"
                                              onsubmit="return confirm('Delete this link? Pausing it instead keeps it for later. Shortcodes that use it will stop showing a card.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="sgh-icon-action is-danger"
                                                    title="Delete" aria-label="Delete {{ $link->name }}"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">{{ $links->links() }}</div>

        <p class="small text-secondary mt-3 mb-0">
            Clicks are counted by your own website: robots and link previews are skipped, and the same visitor clicking again within 30 minutes counts once.
            Clicks are not sales. Your affiliate program's report is the one that decides payment, and its numbers will differ.
        </p>
    @endif
</div>

{{-- The Copy buttons --}}
<script>
    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var text = button.getAttribute('data-copy');
            var done = function () { button.textContent = 'Copied'; setTimeout(function () { button.textContent = 'Copy'; }, 1500); };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).then(done); }
            else { window.prompt('Copy this:', text); }
        });
    });
</script>

@endsection