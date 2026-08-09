@props(['items' => []])
@php($rows = collect($items)->values(); $max = max(1, (float) ($rows->max('value') ?? 1)))
<div class="dth-funnel">
    @forelse($rows as $index => $row)
        @php($value = (float) ($row['value'] ?? 0); $pct = max(12, ($value / $max) * 100))
        <div class="dth-funnel__step" style="width: {{ $pct }}%">
            <span>{{ $row['label'] }}</span><strong>{{ number_format($value) }}</strong>
        </div>
    @empty
        <div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>
    @endforelse
</div>
