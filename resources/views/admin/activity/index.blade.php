@extends('layouts.admin')

@section('title', 'Activity log')
@section('heading', 'Activity log')

@section('content')

@php
    $tz = config('shop.timezone');

    // Plain words for the log's action names
    $labels = [
        'affiliate.created' => 'Link added',
        'affiliate.updated' => 'Link edited',
        'affiliate.paused'  => 'Link paused',
        'affiliate.resumed' => 'Link resumed',
        'affiliate.checked' => 'Link checked',
        'affiliate.deleted' => 'Link deleted',
        'affiliate.blocked' => 'Unsafe address blocked',
    ];
@endphp

<p class="mb-3"><a href="{{ route('admin.affiliate-links.index') }}"><i class="bi bi-arrow-left"></i> Back to affiliate links</a></p>

<div class="sgh-panel">
    <div class="sgh-panel-head">
        <div>
            <h2>Who changed what</h2>
            <p>Newest first. Staff addresses are recorded for security. Passwords, keys and affiliate addresses are never written here.</p>
        </div>
    </div>

    @if ($logs->isEmpty())
        <p class="sgh-panel-empty"><i class="bi bi-clock-history"></i> Nothing has been recorded yet.</p>
    @else
        <div class="table-responsive">
            <table class="table sgh-table align-middle mb-0">
                <thead>
                    <tr><th>When</th><th>Who</th><th>What</th><th>About</th><th>Details</th></tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at->timezone($tz)->format('d M Y, h:i A') }}</td>
                            <td>
                                @if ($log->user)
                                    {{ $log->user->name }}
                                    <small class="d-block text-secondary">{{ $log->ip }}</small>
                                @else
                                    <span class="text-secondary">{{ $log->user_id ? 'Deleted account' : 'Visitor or system' }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $log->action === 'affiliate.blocked' ? 'sgh-badge-out' : ($log->action === 'affiliate.deleted' ? 'sgh-badge-low' : 'sgh-badge-ok') }}">
                                    {{ $labels[$log->action] ?? $log->action }}
                                </span>
                            </td>
                            <td>{{ $log->subject }}</td>
                            <td class="small text-secondary">{{ $log->details }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">{{ $logs->links() }}</div>
    @endif
</div>

@endsection