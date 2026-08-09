@props([
    'items' => [],
    'money' => true,
    'limit' => 8,
    'tone' => 'primary',
])

@php
    $rows = collect($items)->take($limit)->values();
    $max = max(1, (float) ($rows->max('value') ?? 1));
@endphp

<div class="dth-analytics-bars" data-tone="{{ $tone }}">
    @forelse($rows as $row)
        @php($value = (float) ($row['value'] ?? 0))
        <div class="dth-analytics-bar-row">
            <div class="dth-analytics-bar-meta">
                <span title="{{ $row['label'] ?? '' }}">{{ $row['label'] ?? '—' }}</span>
                <strong>{{ $money ? number_format($value, 0, ',', '.').' ₫' : number_format($value) }}</strong>
            </div>
            <div class="dth-analytics-bar-track"><span style="width: {{ max(2, ($value / $max) * 100) }}%"></span></div>
            @if(isset($row['subtitle']))<div class="dth-analytics-bar-subtitle">{{ $row['subtitle'] }}</div>@endif
        </div>
    @empty
        <div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>
    @endforelse
</div>
