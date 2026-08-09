@props([
    'label',
    'value',
    'icon' => 'heroicon-o-chart-bar',
    'tone' => 'primary',
    'delta' => null,
    'helper' => null,
])

<article class="dth-analytics-kpi" data-tone="{{ $tone }}">
    <div class="dth-analytics-kpi__top">
        <span class="dth-analytics-kpi__label">{{ $label }}</span>
        <span class="dth-analytics-kpi__icon"><x-filament::icon :icon="$icon" class="h-5 w-5" /></span>
    </div>
    <div class="dth-analytics-kpi__value">{{ $value }}</div>
    <div class="dth-analytics-kpi__footer">
        @if(is_array($delta))
            <span class="dth-analytics-delta" data-direction="{{ $delta['direction'] ?? 'flat' }}">
                @if(($delta['direction'] ?? '') === 'up') ↑ @elseif(($delta['direction'] ?? '') === 'down') ↓ @else → @endif
                {{ $delta['text'] ?? '0%' }}
            </span>
            <span>{{ __('analytics.vs_previous_period') }}</span>
        @elseif($helper)
            <span>{{ $helper }}</span>
        @endif
    </div>
</article>
