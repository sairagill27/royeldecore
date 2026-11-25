@extends('layouts.admin')

@section('title', 'Assistant usage')
@section('heading', 'Assistant usage')

@section('content')

<p class="mb-3"><a href="{{ route('admin.assistant-answers.index') }}"><i class="bi bi-arrow-left"></i> Back to answers</a></p>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="sgh-stat sgh-stat--sage"><div><small>Questions answered today</small><strong>{{ number_format($totals['today']) }}</strong></div></div></div>
    <div class="col-md-4"><div class="sgh-stat sgh-stat--forest"><div><small>Last 7 days</small><strong>{{ number_format($totals['week']) }}</strong></div></div></div>
    <div class="col-md-4"><div class="sgh-stat sgh-stat--gold"><div><small>Last 30 days</small><strong>{{ number_format($totals['month']) }}</strong></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="sgh-panel h-100">
            <div class="sgh-panel-head"><div><h2>How the questions were answered</h2><p>Saved answers are free. "Limit reached" counts questions that were turned away.</p></div></div>
            <div class="table-responsive">
                <table class="table sgh-table align-middle mb-0">
                    <thead><tr><th>Kind</th><th class="text-end">Today</th><th class="text-end">7 days</th><th class="text-end">30 days</th></tr></thead>
                    <tbody>
                        @foreach ($byMode as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-end">{{ number_format($row['today']) }}</td>
                                <td class="text-end">{{ number_format($row['week']) }}</td>
                                <td class="text-end"><strong>{{ number_format($row['month']) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="sgh-panel h-100">
            <div class="sgh-panel-head"><div><h2>Status</h2></div></div>
            <ul class="list-unstyled small mb-0">
                <li class="mb-2">
                    @if ($status['ai_on'])
                        <span class="badge sgh-badge-ok">AI on</span> The AI key is set. Model: {{ $status['model'] }}.
                    @else
                        <span class="badge sgh-badge-draft">Simple mode</span>
                        {{ $status['key_set'] ? 'The assistant is switched off in the settings.' : 'The AI key is not set, so answers come from your saved answers and your products.' }}
                    @endif
                </li>
                <li class="mb-1">Each visitor: {{ $status['per_person'] }} questions a day</li>
                <li class="mb-1">Each internet address: {{ $status['per_ip'] }} a day</li>
                <li class="mb-1">Whole site: {{ $status['per_site'] }} a day. Used today: {{ $status['site_today'] }}</li>
            </ul>
        </div>
    </div>
</div>

<div class="sgh-panel mb-3">
    <div class="sgh-panel-head"><div><h2>Questions per day</h2><p>The last 14 days. Hover a bar to see its day.</p></div></div>
    <div class="d-flex align-items-end gap-1" style="height: 120px;" role="img" aria-label="Questions answered per day for the last 14 days">
        @foreach ($series as $day => $count)
            <div class="flex-fill rounded-top bg-success" style="height: {{ $max > 0 ? max(2, (int) round($count / $max * 100)) : 2 }}%; opacity: {{ $count > 0 ? '0.9' : '0.25' }};"
                 title="{{ \Illuminate\Support\Carbon::parse($day)->format('d M') }}: {{ $count }}"></div>
        @endforeach
    </div>
</div>

<div class="sgh-panel mb-3">
    <div class="sgh-panel-head"><div><h2>Most used saved answers</h2></div></div>
    @forelse ($top as $item)
        <div class="d-flex justify-content-between border-top py-2 {{ $loop->first ? 'border-0 pt-0' : '' }}">
            <a href="{{ route('admin.assistant-answers.edit', $item->id) }}" class="text-decoration-none">{{ $item->question }}</a>
            <strong>{{ number_format($item->hits) }}</strong>
        </div>
    @empty
        <p class="sgh-panel-empty mb-0"><i class="bi bi-chat-heart"></i> No saved answer has been used yet.</p>
    @endforelse
</div>

<p class="small text-secondary">
    <i class="bi bi-info-circle me-1"></i>
    Only counters are kept here. The questions visitors type are never saved on your server.
</p>

@endsection