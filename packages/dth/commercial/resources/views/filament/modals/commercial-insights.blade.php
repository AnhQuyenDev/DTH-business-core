<div class="dth-com-insights">
    <div class="dth-com-insights__summary">
        <div><span>{{ \Dth\Commercial\Support\UiText::get('overview.open_opportunities', 'Open opportunities') }}</span><strong>{{ number_format((int) ($snapshot['open_opportunities'] ?? 0)) }}</strong></div>
        <div><span>{{ \Dth\Commercial\Support\UiText::get('overview.weighted_pipeline', 'Weighted pipeline') }}</span><strong>{{ number_format((float) ($snapshot['weighted_pipeline_value'] ?? 0), 0, ',', '.') }} ₫</strong></div>
        <div><span>{{ \Dth\Commercial\Support\UiText::get('overview.win_rate', 'Win rate') }}</span><strong>{{ number_format((float) ($snapshot['win_rate'] ?? 0), 1, ',', '.') }}%</strong></div>
    </div>

    <div class="dth-com-insights__list">
        @foreach ($insights as $insight)
            @php
                $icon = match ($insight['level']) {
                    'positive' => 'heroicon-o-check-circle',
                    'warning' => 'heroicon-o-exclamation-triangle',
                    'info' => 'heroicon-o-chart-bar',
                    default => 'heroicon-o-light-bulb',
                };
            @endphp
            <article class="dth-com-insight" data-level="{{ $insight['level'] }}">
                <div class="dth-com-insight__icon">
                    <x-filament::icon :icon="$icon" />
                </div>
                <div class="dth-com-insight__copy">
                    <div class="dth-com-insight__title-row">
                        <strong>{{ $insight['title'] }}</strong>
                        <span>{{ $insight['metric'] }}</span>
                    </div>
                    <p>{{ $insight['body'] }}</p>
                </div>
            </article>
        @endforeach
    </div>
</div>
