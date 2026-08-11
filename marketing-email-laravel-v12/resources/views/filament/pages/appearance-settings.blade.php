<x-filament-panels::page>
    <div class="dth-config-shell">
        <div class="dth-config-launch-grid dth-config-launch-grid--2">
            @if ($canManageDepartments)
                <a href="{{ $departmentUrl }}" class="dth-config-launch-card">
                    <span class="dth-config-launch-card__icon">
                        <x-filament::icon icon="heroicon-o-building-office" class="h-5 w-5" />
                    </span>
                    <span class="dth-config-launch-card__content">
                        <strong>{{ __('configuration.appearance_hub.department_colors') }}</strong>
                        <span>{{ __('configuration.appearance_hub.department_colors_help') }}</span>
                    </span>
                    <span class="dth-config-launch-card__meta">
                        <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4" />
                    </span>
                </a>
            @endif

            @if ($canManageSharedBadges)
                <a href="{{ $sharedBadgeUrl }}" class="dth-config-launch-card">
                    <span class="dth-config-launch-card__icon">
                        <x-filament::icon icon="heroicon-o-swatch" class="h-5 w-5" />
                    </span>
                    <span class="dth-config-launch-card__content">
                        <strong>{{ __('configuration.appearance_hub.shared_badges') }}</strong>
                        <span>{{ __('configuration.appearance_hub.shared_badges_help') }}</span>
                    </span>
                    <span class="dth-config-launch-card__meta">
                        <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4" />
                    </span>
                </a>
            @endif
        </div>

        <div class="dth-config-palette-panel">
            <div class="dth-config-palette-panel__head">
                <strong>{{ __('configuration.appearance_hub.palette_title') }}</strong>
                <span>{{ count($colors) }}</span>
            </div>
            <div class="dth-config-color-orbit" aria-label="{{ __('configuration.appearance_hub.palette_title') }}">
                @foreach ($colors as $key => $label)
                    <span
                        class="dth-config-color-orbit__swatch"
                        style="--swatch-color: {{ $colorHex[$key] ?? '#6b7280' }}"
                        title="{{ $label }}"
                        aria-label="{{ $label }}"
                    ></span>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
