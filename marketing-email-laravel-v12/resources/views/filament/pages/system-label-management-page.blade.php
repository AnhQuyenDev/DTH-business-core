<x-filament-panels::page>
    <style>
        .dth-label-directory { display: grid; gap: 1rem; }
        .dth-label-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
        .dth-label-summary__item, .dth-label-panel { border: 1px solid rgb(255 255 255 / .08); background: rgb(24 24 27 / .82); border-radius: .8rem; }
        .dth-label-summary__item { padding: .9rem 1rem; display: flex; align-items: center; gap: .8rem; }
        .dth-label-summary__icon { width: 2.2rem; height: 2.2rem; flex: 0 0 2.2rem; margin: 0; border-radius: .65rem; display: inline-grid; place-items: center; color: rgb(245 158 11); background: rgb(245 158 11 / .12); }
        .dth-label-summary__icon > svg { display: block; width: 1.25rem; height: 1.25rem; margin: 0; }
        .dth-label-summary__item strong { display: block; font-size: 1.2rem; line-height: 1.2; color: white; }
        .dth-label-summary__item > div > span { display: block; margin-top: .15rem; color: rgb(161 161 170); font-size: .78rem; }
        .dth-label-panel { overflow: hidden; }
        .dth-label-panel__head { padding: .85rem 1rem; border-bottom: 1px solid rgb(255 255 255 / .08); display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .dth-label-panel__head strong { color: white; font-size: .9rem; }
        .dth-label-panel__head span { color: rgb(161 161 170); font-size: .78rem; }
        .dth-label-palette { padding: .9rem 1rem; display: flex; flex-wrap: wrap; gap: .65rem; align-items: center; }
        .dth-label-swatch { position: relative; width: 2rem; height: 2rem; border-radius: 9999px; border: 2px solid color-mix(in srgb, var(--swatch) 70%, #111827 30%); background: var(--swatch); box-shadow: inset 0 0 0 2px rgb(255 255 255 / .22), 0 0 0 1px rgb(255 255 255 / .08); transition: transform .14s ease, box-shadow .14s ease; }
        .dth-label-swatch:hover, .dth-label-swatch:focus-visible, .dth-label-swatch.is-selected { transform: scale(1.08); outline: none; box-shadow: inset 0 0 0 2px rgb(255 255 255 / .25), 0 0 0 2px white, 0 0 0 4px var(--swatch), 0 0 14px color-mix(in srgb, var(--swatch) 68%, transparent); }
        .dth-label-swatch__count { position: absolute; top: -.45rem; right: -.5rem; min-width: 1rem; height: 1rem; padding: 0 .2rem; border-radius: 9999px; background: rgb(39 39 42); border: 1px solid rgb(255 255 255 / .15); color: white; font-size: .62rem; line-height: .9rem; text-align: center; }
        .dth-label-filters { padding: .9rem 1rem; display: grid; grid-template-columns: minmax(15rem, 2fr) repeat(2, minmax(10rem, 1fr)) auto; gap: .7rem; border-bottom: 1px solid rgb(255 255 255 / .08); }
        .dth-label-control { width: 100%; min-height: 2.4rem; padding: .48rem .7rem; border-radius: .55rem; border: 1px solid rgb(255 255 255 / .14); background: rgb(39 39 42); color: white; font-size: .84rem; }
        .dth-label-control:focus { border-color: rgb(245 158 11); outline: none; box-shadow: 0 0 0 2px rgb(245 158 11 / .18); }
        .dth-label-select { position: relative; min-width: 0; }
        .dth-label-select select { appearance: none !important; -webkit-appearance: none !important; padding-right: 2.35rem; background-image: none !important; background-repeat: no-repeat !important; }
        .dth-label-select > svg { position: absolute; top: 50%; right: .72rem; width: 1rem; height: 1rem; color: rgb(113 113 122); pointer-events: none; transform: translateY(-50%); }
        .dth-label-table-wrap { overflow-x: auto; }
        .dth-label-table { width: 100%; border-collapse: collapse; min-width: 1050px; }
        .dth-label-table th { padding: .72rem .85rem; text-align: left; background: rgb(39 39 42); color: rgb(228 228 231); font-size: .75rem; font-weight: 650; }
        .dth-label-table td { padding: .78rem .85rem; border-top: 1px solid rgb(255 255 255 / .07); color: rgb(228 228 231); font-size: .82rem; vertical-align: middle; }
        .dth-label-table tbody tr:hover { background: rgb(255 255 255 / .025); }
        .dth-label-name { display: flex; flex-direction: column; gap: .12rem; }
        .dth-label-name strong { color: white; }
        .dth-label-name code { color: rgb(113 113 122); font-size: .65rem; }
        .dth-label-chip { display: inline-flex; align-items: center; gap: .38rem; padding: .24rem .48rem; border-radius: .42rem; border: 1px solid color-mix(in srgb, var(--chip) 52%, transparent); background: color-mix(in srgb, var(--chip) 12%, transparent); color: color-mix(in srgb, var(--chip) 82%, white 18%); white-space: nowrap; }
        .dth-label-chip::before { content: ''; width: .58rem; height: .58rem; border-radius: 9999px; background: var(--chip); box-shadow: 0 0 0 1px rgb(255 255 255 / .16); }
        .dth-label-state { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .45rem; border-radius: 9999px; font-size: .7rem; }
        .dth-label-state--custom { color: rgb(253 186 116); background: rgb(249 115 22 / .12); border: 1px solid rgb(249 115 22 / .25); }
        .dth-label-state--default { color: rgb(134 239 172); background: rgb(34 197 94 / .1); border: 1px solid rgb(34 197 94 / .22); }
        .dth-label-usage summary { cursor: pointer; color: rgb(251 191 36); font-size: .76rem; }
        .dth-label-usage ul { margin: .4rem 0 0 1rem; color: rgb(161 161 170); font-size: .72rem; list-style: disc; }
        .dth-label-actions { display: flex; gap: .35rem; justify-content: flex-end; }
        .dth-label-empty { padding: 2.5rem 1rem; text-align: center; color: rgb(161 161 170); }
        .dth-label-initial { padding: 1rem; display: flex; align-items: center; justify-content: center; gap: .5rem; color: rgb(161 161 170); font-size: .78rem; }
        .dth-label-initial svg { width: 1rem; height: 1rem; flex: 0 0 auto; }
        .dth-label-editor-summary { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem; margin-bottom: .85rem; }
        .dth-label-editor-summary > div { padding: .7rem .8rem; border: 1px solid rgb(255 255 255 / .08); border-radius: .6rem; background: rgb(39 39 42 / .7); }
        .dth-label-editor-summary span { display: block; color: rgb(161 161 170); font-size: .7rem; }
        .dth-label-editor-summary strong { display: block; margin-top: .2rem; color: white; font-size: .84rem; }
        .dth-label-warning { display: flex; gap: .65rem; padding: .78rem .85rem; border-radius: .62rem; margin: .75rem 0; }
        .dth-label-warning--business { color: rgb(253 230 138); background: rgb(245 158 11 / .1); border: 1px solid rgb(245 158 11 / .25); }
        .dth-label-warning--critical { color: rgb(254 202 202); background: rgb(239 68 68 / .1); border: 1px solid rgb(239 68 68 / .25); }
        .dth-label-warning--semantic { color: rgb(186 230 253); background: rgb(14 165 233 / .1); border: 1px solid rgb(14 165 233 / .25); }
        .dth-label-warning p { margin: 0; font-size: .78rem; line-height: 1.5; }
        .dth-label-field { margin-top: .85rem; }
        .dth-label-field > label { display: block; margin-bottom: .42rem; color: white; font-size: .78rem; font-weight: 600; }
        .dth-label-editor-palette { display: flex; flex-wrap: wrap; gap: .7rem; padding: .35rem .2rem .55rem; }
        .dth-label-checkbox { display: flex; align-items: flex-start; gap: .55rem; color: rgb(228 228 231); font-size: .78rem; line-height: 1.45; }
        .dth-label-checkbox input { margin-top: .18rem; accent-color: rgb(245 158 11); }
        .dth-label-error { margin-top: .3rem; color: rgb(248 113 113); font-size: .72rem; }
        .dth-label-modal-actions { display: flex; justify-content: flex-end; gap: .55rem; margin-top: 1rem; }
        @media (max-width: 900px) { .dth-label-summary { grid-template-columns: 1fr; } .dth-label-filters { grid-template-columns: 1fr; } .dth-label-editor-summary { grid-template-columns: 1fr; } }
        html:not(.dark) .dth-label-summary__item, html:not(.dark) .dth-label-panel { background: white; border-color: rgb(24 24 27 / .1); }
        html:not(.dark) .dth-label-summary__item strong, html:not(.dark) .dth-label-panel__head strong, html:not(.dark) .dth-label-name strong, html:not(.dark) .dth-label-editor-summary strong, html:not(.dark) .dth-label-field > label { color: rgb(24 24 27); }
        html:not(.dark) .dth-label-table th, html:not(.dark) .dth-label-control { background: rgb(244 244 245); color: rgb(24 24 27); }
        html:not(.dark) .dth-label-table td { color: rgb(39 39 42); border-color: rgb(24 24 27 / .08); }
        html:not(.dark) .dth-label-editor-summary > div { background: rgb(250 250 250); border-color: rgb(24 24 27 / .1); }
        html:not(.dark) .dth-label-checkbox { color: rgb(39 39 42); }
    </style>

    <div class="dth-label-directory">
        <div class="dth-label-summary">
            <div class="dth-label-summary__item">
                <span class="dth-label-summary__icon"><x-filament::icon icon="heroicon-o-tag" class="h-5 w-5" /></span>
                <div><strong>{{ $totalLabels }}</strong><span>{{ __('configuration.system_labels.summary.total') }}</span></div>
            </div>
            <div class="dth-label-summary__item">
                <span class="dth-label-summary__icon"><x-filament::icon icon="heroicon-o-pencil-square" class="h-5 w-5" /></span>
                <div><strong>{{ $customizedCount }}</strong><span>{{ __('configuration.system_labels.summary.customized') }}</span></div>
            </div>
            <div class="dth-label-summary__item">
                <span class="dth-label-summary__icon"><x-filament::icon icon="heroicon-o-swatch" class="h-5 w-5" /></span>
                <div><strong>{{ $paletteUsage->count() }}</strong><span>{{ __('configuration.system_labels.summary.colors_in_use') }}</span></div>
            </div>
        </div>

        <section class="dth-label-panel">
            <div class="dth-label-panel__head">
                <div><strong>{{ __('configuration.system_labels.palette_title') }}</strong><span> · {{ __('configuration.system_labels.palette_help') }}</span></div>
                @if ($colorFilter !== '')
                    <x-filament::button size="xs" color="gray" icon="heroicon-o-x-mark" wire:click="selectColorFilter('{{ $colorFilter }}')">
                        {{ __('configuration.system_labels.clear_color_filter') }}
                    </x-filament::button>
                @endif
            </div>
            <div class="dth-label-palette">
                @foreach ($paletteUsage as $usage)
                    <button
                        type="button"
                        class="dth-label-swatch {{ $colorFilter === $usage['hex_key'] ? 'is-selected' : '' }}"
                        style="--swatch: {{ $usage['hex'] }}"
                        title="{{ __('configuration.system_labels.color_usage_tooltip', ['labels' => $usage['definitions'], 'records' => $usage['records'], 'names' => $usage['labels']]) }}"
                        wire:click="selectColorFilter('{{ $usage['hex_key'] }}')"
                    >
                        <span class="dth-label-swatch__count">{{ $usage['definitions'] }}</span>
                        <span class="sr-only">{{ $usage['labels'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        <section class="dth-label-panel">
            <div class="dth-label-panel__head">
                <div>
                    <strong>{{ __('configuration.system_labels.directory_title') }}</strong>
                    <span> · {{ $showResults
                        ? trans_choice('configuration.system_labels.results', $labels->count(), ['count' => $labels->count()])
                        : __('configuration.system_labels.filter_to_view') }}</span>
                </div>
            </div>

            <div class="dth-label-filters">
                <input class="dth-label-control" type="search" wire:model.live.debounce.500ms="search" placeholder="{{ __('configuration.system_labels.search_placeholder') }}">
                <div class="dth-label-select">
                    <select class="dth-label-control" wire:model.live="moduleFilter">
                        <option value="">{{ __('configuration.system_labels.all_modules') }}</option>
                        @foreach ($moduleOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-filament::icon icon="heroicon-m-chevron-down" />
                </div>
                <div class="dth-label-select">
                    <select class="dth-label-control" wire:model.live="groupFilter">
                        <option value="">{{ __('configuration.system_labels.all_groups') }}</option>
                        @foreach ($groupOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-filament::icon icon="heroicon-m-chevron-down" />
                </div>
                <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="clearFilters">
                    {{ __('configuration.system_labels.clear_filters') }}
                </x-filament::button>
            </div>

            @if ($showResults)
                <div class="dth-label-table-wrap">
                    <table class="dth-label-table">
                        <thead>
                            <tr>
                                <th>{{ __('configuration.system_labels.module') }}</th>
                                <th>{{ __('configuration.system_labels.group') }}</th>
                                <th>{{ __('configuration.system_labels.label') }}</th>
                                <th>{{ __('configuration.system_labels.default_color') }}</th>
                                <th>{{ __('configuration.system_labels.current_color') }}</th>
                                <th>{{ __('configuration.system_labels.usage') }}</th>
                                <th>{{ __('configuration.system_labels.state') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($labels as $definition)
                                <tr wire:key="system-label-{{ $definition->identifier }}">
                                    <td>{{ $definition->moduleLabel }}</td>
                                    <td>{{ $definition->groupLabel }}</td>
                                    <td>
                                        <span class="dth-label-name">
                                            <strong>{{ $definition->label }}</strong>
                                            <code>{{ $definition->key }}</code>
                                        </span>
                                    </td>
                                    <td><span class="dth-label-chip" style="--chip: {{ $definition->defaultHex() }}">{{ $colorOptions[$definition->defaultColor] ?? $definition->defaultColor }}</span></td>
                                    <td><span class="dth-label-chip" style="--chip: {{ $definition->currentHex() }}">{{ $colorOptions[$definition->currentColor] ?? $definition->currentColor }}</span></td>
                                    <td>
                                        <details class="dth-label-usage">
                                            <summary>{{ trans_choice('configuration.system_labels.usage_count', $definition->usageCount, ['count' => $definition->usageCount]) }}</summary>
                                            <ul>
                                                @foreach ($definition->usageLocations as $location)
                                                    <li>{{ $location }}</li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    </td>
                                    <td>
                                        <span class="dth-label-state {{ $definition->isCustomized() ? 'dth-label-state--custom' : 'dth-label-state--default' }}">
                                            <x-filament::icon :icon="$definition->isCustomized() ? 'heroicon-o-pencil' : 'heroicon-o-check-circle'" class="h-3.5 w-3.5" />
                                            {{ $definition->isCustomized() ? __('configuration.system_labels.customized') : __('configuration.system_labels.using_default') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dth-label-actions">
                                            <x-filament::icon-button
                                                icon="heroicon-o-pencil-square"
                                                color="warning"
                                                :tooltip="__('configuration.system_labels.edit')"
                                                wire:click="openColorEditor('{{ $definition->identifier }}')"
                                            />
                                            @if ($definition->isCustomized())
                                                <x-filament::icon-button
                                                    icon="heroicon-o-arrow-uturn-left"
                                                    color="gray"
                                                    :tooltip="__('configuration.system_labels.reset')"
                                                    wire:click="openColorEditor('{{ $definition->identifier }}', 'reset')"
                                                />
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="dth-label-empty">{{ __('configuration.system_labels.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="dth-label-initial">
                    <x-filament::icon icon="heroicon-o-funnel" />
                    <span>{{ __('configuration.system_labels.filter_to_view_help') }}</span>
                </div>
            @endif
        </section>
    </div>

    <x-filament::modal id="system-label-color-editor" width="3xl">
        @if ($editingDefinition)
            <x-slot name="heading">
                {{ $editingMode === 'reset' ? __('configuration.system_labels.reset_title') : __('configuration.system_labels.edit_title') }}
            </x-slot>

            <x-slot name="description">
                {{ __('configuration.system_labels.edit_description') }}
            </x-slot>

            <div class="dth-label-editor-summary">
                <div><span>{{ __('configuration.system_labels.group') }}</span><strong>{{ $editingDefinition->groupLabel }}</strong></div>
                <div><span>{{ __('configuration.system_labels.label') }}</span><strong>{{ $editingDefinition->label }}</strong></div>
                <div><span>{{ __('configuration.system_labels.current_color') }}</span><strong class="dth-label-chip" style="--chip: {{ $editingDefinition->currentHex() }}">{{ $colorOptions[$editingDefinition->currentColor] ?? $editingDefinition->currentColor }}</strong></div>
                <div><span>{{ __('configuration.system_labels.affected_records') }}</span><strong>{{ $editingDefinition->usageCount }}</strong></div>
            </div>

            <div class="dth-label-warning {{ $editingDefinition->requiresReason() ? 'dth-label-warning--critical' : 'dth-label-warning--business' }}">
                <x-filament::icon :icon="$editingDefinition->requiresReason() ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-information-circle'" class="h-5 w-5 shrink-0" />
                <p>
                    {{ $editingDefinition->requiresReason()
                        ? __('configuration.system_labels.warning_critical', ['count' => $editingDefinition->usageCount])
                        : __('configuration.system_labels.warning_business', ['count' => $editingDefinition->usageCount]) }}
                    <strong>{{ __('configuration.system_labels.warning_visual_only') }}</strong>
                </p>
            </div>

            @if ($editingMode === 'change')
                <div class="dth-label-field">
                    <label>{{ __('configuration.system_labels.choose_color') }}</label>
                    <div class="dth-label-editor-palette">
                        @foreach ($colorOptions as $key => $label)
                            <button
                                type="button"
                                class="dth-label-swatch {{ $selectedColor === $key ? 'is-selected' : '' }}"
                                style="--swatch: {{ $colorHex[$key] ?? '#6b7280' }}"
                                title="{{ $label }}"
                                wire:click="selectEditingColor('{{ $key }}')"
                            ><span class="sr-only">{{ $label }}</span></button>
                        @endforeach
                    </div>
                    @error('selectedColor') <p class="dth-label-error">{{ $message }}</p> @enderror
                </div>

                @if ($editingDefinition->type === 'semantic' && $selectedColor !== $editingDefinition->defaultColor)
                    <div class="dth-label-warning dth-label-warning--semantic">
                        <x-filament::icon icon="heroicon-o-shield-exclamation" class="h-5 w-5 shrink-0" />
                        <p>{{ __('configuration.system_labels.semantic_override_warning', ['default' => $colorOptions[$editingDefinition->defaultColor] ?? $editingDefinition->defaultColor]) }}</p>
                    </div>
                @endif
            @else
                <div class="dth-label-warning dth-label-warning--semantic">
                    <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="h-5 w-5 shrink-0" />
                    <p>{{ __('configuration.system_labels.reset_description', ['color' => $colorOptions[$editingDefinition->defaultColor] ?? $editingDefinition->defaultColor]) }}</p>
                </div>
            @endif

            <div class="dth-label-field">
                <label class="dth-label-checkbox">
                    <input type="checkbox" wire:model.live="acknowledged">
                    <span>{{ __('configuration.system_labels.acknowledgement') }}</span>
                </label>
                @error('acknowledged') <p class="dth-label-error">{{ $message }}</p> @enderror
            </div>

            @if ($editingDefinition->requiresReason())
                <div class="dth-label-field">
                    <label for="system-label-change-reason">{{ __('configuration.system_labels.change_reason') }} <span class="text-danger-500">*</span></label>
                    <textarea id="system-label-change-reason" class="dth-label-control" rows="3" maxlength="500" wire:model.live.debounce.300ms="changeReason" placeholder="{{ __('configuration.system_labels.change_reason_placeholder') }}"></textarea>
                    @error('changeReason') <p class="dth-label-error">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="dth-label-modal-actions">
                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'system-label-color-editor' })" icon="heroicon-o-x-mark">
                    {{ __('action.cancel') }}
                </x-filament::button>
                <x-filament::button color="warning" wire:click="saveLabelColor" wire:loading.attr="disabled" icon="heroicon-o-check-circle">
                    {{ $editingMode === 'reset' ? __('configuration.system_labels.confirm_reset') : __('configuration.system_labels.confirm_change') }}
                </x-filament::button>
            </div>
        @endif
    </x-filament::modal>
</x-filament-panels::page>
