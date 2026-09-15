<x-filament-widgets::widget>
    <section class="dth-email-panel dth-email-funnel-panel">
        <header class="dth-email-panel-header">
            <div>
                <h3>{{ $heading }}</h3>
                <p>{{ $description }}</p>
            </div>
        </header>

        <div class="dth-email-funnel">
            <div class="dth-email-funnel-shapes" aria-hidden="true">
                @foreach ($steps as $index => $step)
                    <div class="dth-email-funnel-shape" data-tone="{{ $step['tone'] }}" style="--funnel-level: {{ $index }};">
                        {{ number_format($step['value'], 0, ',', '.') }}
                    </div>
                @endforeach
            </div>

            <div class="dth-email-funnel-labels">
                @foreach ($steps as $step)
                    <div class="dth-email-funnel-label">
                        <span>{{ $step['label'] }}</span>
                        <strong>{{ number_format($step['percent'], 1, ',', '.') }}%</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
