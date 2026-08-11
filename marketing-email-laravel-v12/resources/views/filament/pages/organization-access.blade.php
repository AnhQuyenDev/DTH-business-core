<x-filament-panels::page>
    <div class="dth-config-shell">
        <div class="dth-config-toolbar">
            <p>{{ __('configuration.organization.description') }}</p>
            @if (count($attention))
                <span class="dth-config-toolbar__status dth-config-toolbar__status--warning">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4" />
                    {{ __('configuration.organization.needs_attention') }} · {{ count($attention) }}
                </span>
            @else
                <span class="dth-config-toolbar__status dth-config-toolbar__status--success">
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4" />
                    {{ __('configuration.organization.all_good') }}
                </span>
            @endif
        </div>

        <div class="dth-config-launch-grid dth-config-launch-grid--3">
            @foreach (['departments', 'positions', 'staff'] as $key)
                @php($card = $cards[$key])
                @if ($card['visible'])
                    <a href="{{ $card['url'] }}" class="dth-config-launch-card">
                        <span class="dth-config-launch-card__icon">
                            <x-filament::icon :icon="$card['icon']" class="h-5 w-5" />
                        </span>
                        <span class="dth-config-launch-card__content">
                            <strong>{{ __('configuration.organization.cards.'.$key.'.title') }}</strong>
                            <span>{{ __('configuration.organization.cards.'.$key.'.description') }}</span>
                        </span>
                        <span class="dth-config-launch-card__meta">
                            <b>{{ $card['count'] }}</b>
                            <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4" />
                        </span>
                    </a>
                @endif
            @endforeach
        </div>

        @if (count($attention))
            <div class="dth-config-attention-panel">
                @foreach ($attention as $message)
                    <div class="dth-config-attention-panel__item">
                        <x-filament::icon icon="heroicon-o-exclamation-circle" class="h-4 w-4" />
                        <span>{{ $message }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
