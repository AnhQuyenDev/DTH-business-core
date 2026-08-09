@props([
    'current' => [],
    'previous' => [],
    'currentLabel' => null,
    'previousLabel' => null,
    'money' => true,
    'height' => 220,
])

@php
    $currentRows = collect($current)->values();
    $previousRows = collect($previous)->values();
    $count = max(1, $currentRows->count());
    $allValues = $currentRows->pluck('value')->merge($previousRows->pluck('value'))->map(fn ($v) => (float) $v);
    $maxValue = max(1, (float) ($allValues->max() ?? 1));
    $width = 720;
    $chartHeight = 180;
    $top = 18;
    $bottom = 34;
    $usableHeight = $chartHeight - $top;
    $xStep = $count > 1 ? ($width - 50) / ($count - 1) : 0;
    $toPoints = function ($rows) use ($width, $count, $xStep, $maxValue, $top, $usableHeight) {
        return collect($rows)->values()->map(function ($row, $index) use ($width, $count, $xStep, $maxValue, $top, $usableHeight) {
            $x = $count > 1 ? 25 + ($index * $xStep) : $width / 2;
            $ratio = min(1, max(0, ((float) ($row['value'] ?? 0)) / $maxValue));
            $y = $top + ($usableHeight * (1 - $ratio));
            return round($x, 2).','.round($y, 2);
        })->implode(' ');
    };
    $currentPoints = $toPoints($currentRows);
    $previousPoints = $toPoints($previousRows);
    $areaPoints = $currentRows->isNotEmpty()
        ? '25,'.($chartHeight + 2).' '.$currentPoints.' '.($count > 1 ? ($width - 25) : ($width / 2)).','.($chartHeight + 2)
        : '';
@endphp

<div class="dth-line-chart">
    <div class="dth-chart-legend">
        <span><i class="dth-chart-dot dth-chart-dot--current"></i>{{ $currentLabel ?: __('analytics.current_period') }}</span>
        @if($previousRows->isNotEmpty())<span><i class="dth-chart-dot dth-chart-dot--previous"></i>{{ $previousLabel ?: __('analytics.previous_period') }}</span>@endif
    </div>
    <svg viewBox="0 0 {{ $width }} {{ $chartHeight + $bottom }}" role="img" aria-label="{{ __('analytics.revenue_trend') }}" class="dth-line-chart__svg">
        @foreach([0, .25, .5, .75, 1] as $tick)
            @php($y = $top + ($usableHeight * (1 - $tick)))
            <line x1="25" y1="{{ $y }}" x2="{{ $width - 25 }}" y2="{{ $y }}" class="dth-chart-grid-line" />
            <text x="25" y="{{ max(10, $y - 4) }}" class="dth-chart-axis-value">{{ $money ? number_format(($maxValue * $tick) / 1000000, 1).'M' : number_format($maxValue * $tick) }}</text>
        @endforeach

        @if($areaPoints)<polygon points="{{ $areaPoints }}" class="dth-chart-area" />@endif
        @if($previousPoints)<polyline points="{{ $previousPoints }}" class="dth-chart-line dth-chart-line--previous" />@endif
        @if($currentPoints)<polyline points="{{ $currentPoints }}" class="dth-chart-line dth-chart-line--current" />@endif

        @foreach($currentRows as $index => $row)
            @php($x = $count > 1 ? 25 + ($index * $xStep) : $width / 2)
            <text x="{{ $x }}" y="{{ $chartHeight + 26 }}" text-anchor="middle" class="dth-chart-axis-label">{{ $row['label'] ?? '' }}</text>
        @endforeach
    </svg>
</div>
