@extends('layouts.admin')

@section('title', 'Affiliate analytics')
@section('heading', 'Affiliate analytics')

@section('content')

<p class="mb-3"><a href="{{ route('admin.affiliate-links.index') }}"><i class="bi bi-arrow-left"></i> Back to links</a></p>

{{-- ============ TOTALS ============ --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="sgh-stat sgh-stat--sage"><div><small>Today</small><strong>{{ number_format($totals['today']) }}</strong></div></div></div>
    <div class="col-6 col-md-3"><div class="sgh-stat sgh-stat--forest"><div><small>Last 7 days</small><strong>{{ number_format($totals['week']) }}</strong></div></div></div>
    <div class="col-6 col-md-3"><div class="sgh-stat sgh-stat--gold"><div><small>Last 30 days</small><strong>{{ number_format($totals['month']) }}</strong></div></div></div>
    <div class="col-6 col-md-3"><div class="sgh-stat sgh-stat--clay"><div><small>All time</small><strong>{{ number_format($totals['all']) }}</strong></div></div></div>
</div>

{{-- ============ THE LAST 30 DAYS ============ --}}
<div class="sgh-panel mb-3">
    <div class="sgh-panel-head"><div><h2>Clicks per day</h2><p>The last 30 days. Hover a bar to see its day.</p></div></div>

    <div class="d-flex align-items-end gap-1" style="height: 140px;" role="img"
         aria-label="Clicks per day for the last 30 days. {{ $totals['month'] }} clicks in total.">
        @foreach ($series as $day => $count)
            <div class="flex-fill rounded-top bg-success" style="height: {{ $max > 0 ? max(2, (int) round($count / $max * 100)) : 2 }}%; opacity: {{ $count > 0 ? '0.9' : '0.25' }};"
                 title="{{ \Illuminate\Support\Carbon::parse($day)->format('d M') }}: {{ $count }}"></div>
        @endforeach
    </div>
    <div class="d-flex justify-content-between small text-secondary mt-1">
        <span>{{ \Illuminate\Support\Carbon::parse($series->keys()->first())->format('d M') }}</span>
        <span>{{ \Illuminate\Support\Carbon::parse($series->keys()->last())->format('d M') }}</span>
    </div>
</div>

<div class="row g-3 mb-3">

    {{-- ============ TOP LINKS ============ --}}
    <div class="col-lg-7">
        <div class="sgh-panel h-100">
            <div class="sgh-panel-head"><div><h2>Top links</h2><p>Clicks in the last 30 days.</p></div></div>

            @if ($top->isEmpty())
                <p class="sgh-panel-empty"><i class="bi bi-graph-up"></i> No clicks yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table sgh-table align-middle mb-0">
                        <thead><tr><th>Link</th><th class="text-end">30 days</th><th class="text-end">7 days</th></tr></thead>
                        <tbody>
                            @foreach ($top as $row)
                                <tr>
                                    {{-- {{ }} escapes the text (safe) --}}
                                    <td>
                                        <a href="{{ route('admin.affiliate-links.edit', $row->id) }}" class="text-decoration-none fw-semibold">{{ $row->name }}</a>
                                        <small class="d-block text-secondary">{{ $row->merchant }}@if ($row->section) &middot; {{ $row->section }}@endif</small>
                                    </td>
                                    <td class="text-end"><strong>{{ number_format((int) $row->clicks30) }}</strong></td>
                                    <td class="text-end">{{ number_format((int) $row->clicks7) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ============ BY SECTION ============ --}}
    <div class="col-lg-5">
        <div class="sgh-panel h-100">
            <div class="sgh-panel-head"><div><h2>By section</h2><p>Clicks in the last 30 days.</p></div></div>

            @if ($bySection->isEmpty())
                <p class="sgh-panel-empty"><i class="bi bi-graph-up"></i> No clicks yet.</p>
            @else
                @foreach ($bySection as $row)
                    <div class="d-flex justify-content-between border-top py-2 {{ $loop->first ? 'border-0 pt-0' : '' }}">
                        <span>{{ $row->section_name }}</span>
                        <strong>{{ number_format((int) $row->clicks30) }}</strong>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

{{-- ============ LINKS TO CHECK ============ --}}
<div class="sgh-panel mb-3">
    <div class="sgh-panel-head">
        <div>
            <h2>Links to check</h2>
            <p>Active links that nobody has opened and checked for {{ (int) config('affiliate.recheck_days', 30) }} days or more. Stores remove products, so open each one and see that it still works.</p>
        </div>
    </div>

    @forelse ($needsCheck as $link)
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-top py-2 {{ $loop->first ? 'border-0 pt-0' : '' }}">
            <span>
                <a href="{{ route('admin.affiliate-links.edit', $link) }}" class="text-decoration-none fw-semibold">{{ $link->name }}</a>
                <small class="text-secondary">&middot; {{ $link->merchant }} &middot;
                    {{ $link->last_checked_at ? 'checked ' . $link->last_checked_at->diffForHumans() : 'never checked' }}</small>
            </span>
            <form action="{{ route('admin.affiliate-links.checked', $link) }}" method="POST">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill">I checked it</button>
            </form>
        </div>
    @empty
        <p class="text-secondary small mb-0"><i class="bi bi-check2-circle me-1"></i> Every active link was checked recently.</p>
    @endforelse
</div>

<p class="small text-secondary">
    <i class="bi bi-info-circle me-1"></i>
    Clicks are not sales and not commission. They count people who clicked a link on this website (robots and link previews are skipped, and repeat clicks from the same visitor within
    {{ (int) config('affiliate.dedupe_minutes', 30) }} minutes count once). Direct links are not counted here. Your affiliate program's report shows what was really bought.
</p>

@endsection