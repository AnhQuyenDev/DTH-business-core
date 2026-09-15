<x-filament-widgets::widget class="dth-email-kpi-widget">
    <div class="dth-email-kpi-grid">
        @foreach ($stats as $stat)
            <a
                @if (filled($stat['url'])) href="{{ $stat['url'] }}" @else role="group" aria-disabled="true" @endif
                class="dth-email-kpi-card"
                data-tone="{{ $stat['tone'] }}"
            >
                <div class="dth-email-kpi-icon">
                    <x-filament::icon :icon="$stat['icon']" />
                </div>

                <div class="dth-email-kpi-body">
                    <div class="dth-email-kpi-label">{{ $stat['label'] }}</div>
                    <div class="dth-email-kpi-value">{{ $stat['value'] }}</div>
                    <div class="dth-email-kpi-delta" data-color="{{ $stat['meta']['color'] }}">
                        <x-filament::icon :icon="$stat['meta']['icon']" />
                        <span>{{ $stat['meta']['description'] }}</span>
                    </div>
                </div>

                @if (filled($stat['sparkline']))
                    <svg class="dth-email-kpi-sparkline" viewBox="0 0 92 30" preserveAspectRatio="none" aria-hidden="true">
                        <polygon points="0,30 {{ $stat['sparkline'] }} 92,30" class="dth-email-kpi-sparkline-area" />
                        <polyline points="{{ $stat['sparkline'] }}" fill="none" vector-effect="non-scaling-stroke" />
                    </svg>
                @endif
            </a>
        @endforeach
    </div>
</x-filament-widgets::widget>
