{{-- The answer: one row per result, then a few tips. Used by the page when JavaScript is off. --}}
@foreach ($result['results'] ?? [] as $row)
    <div class="sgh-res {{ ! empty($row['main']) ? 'is-main' : '' }}">
        <span class="sgh-res-label">{{ $row['label'] }}</span>
        <strong class="sgh-res-value">{{ $row['value'] }}</strong>
        @if (! empty($row['note']))
            <small class="sgh-res-note">{{ $row['note'] }}</small>
        @endif
    </div>
@endforeach

@if (! empty($result['tips']))
    <div class="sgh-res-tips">
        <strong>Good to know</strong>
        <ul>
            @foreach ($result['tips'] as $tip)
                <li>{{ $tip }}</li>
            @endforeach
        </ul>
    </div>
@endif