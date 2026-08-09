@props([
    'items' => [],
    'money' => true,
    'centerLabel' => null,
])

@php
    $rows = collect($items)->filter(fn ($row) => (float) ($row['value'] ?? 0) > 0)->take(7)->values();
    $total = max(0.0, (float) $rows->sum('value'));
    $palette = ['#f59e0b','#22c55e','#3b82f6','#a855f7','#ef4444','#14b8a6','#64748b'];
    $stops = [];
    $cursor = 0.0;
    foreach ($rows as $index => $row) {
        $portion = $total > 0 ? (((float) $row['value']) / $total) * 100 : 0;
        $next = $cursor + $portion;
        $stops[] = $palette[$index % count($palette)].' '.round($cursor, 2).'% '.round($next, 2).'%';
        $cursor = $next;
    }
    $gradient = $stops !== [] ? 'conic-gradient('.implode(', ', $stops).')' : 'conic-gradient(#374151 0 100%)';
@endphp

<div class="dth-donut-layout">
    <div class="dth-donut" style="background: {{ $gradient }}">
        <div class="dth-donut__hole">
            <strong>{{ $money ? number_format($total / 1000000, 1).'M' : number_format($total) }}</strong>
            <span>{{ $centerLabel ?: __('analytics.total') }}</span>
        </div>
    </div>
    <div class="dth-donut-legend">
        @forelse($rows as $index => $row)
            @php
                $value = (float) ($row['value'] ?? 0);
                $pct = $total > 0 ? ($value / $total) * 100 : 0;
            @endphp
            <div class="dth-donut-legend__row">
                <i style="background: {{ $palette[$index % count($palette)] }}"></i>
                <span class="dth-donut-legend__label" title="{{ $row['label'] ?? '' }}">{{ $row['label'] ?? '—' }}</span>
                <strong>{{ number_format($pct, 1) }}%</strong>
            </div>
        @empty
            <div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>
        @endforelse
    </div>
</div>
