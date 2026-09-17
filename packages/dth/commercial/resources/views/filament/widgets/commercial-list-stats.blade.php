<x-filament-widgets::widget class="dth-com-list-stats-widget">
    <div class="dth-com-list-stats">
        @foreach ($stats as $stat)
            <article class="dth-com-list-stat" data-tone="{{ $stat['tone'] }}">
                <div class="dth-com-list-stat__icon">
                    <x-filament::icon :icon="$stat['icon']" />
                </div>
                <div class="dth-com-list-stat__body">
                    <span class="dth-com-list-stat__label">{{ $stat['label'] }}</span>
                    <strong class="dth-com-list-stat__value">{{ $stat['value'] }}</strong>
                    <span class="dth-com-list-stat__meta">{{ $stat['meta'] }}</span>
                </div>
            </article>
        @endforeach
    </div>
</x-filament-widgets::widget>
