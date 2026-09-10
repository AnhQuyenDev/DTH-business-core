<x-filament::section
    :heading="\Dth\Email\Support\UiText::get('dashboard.filters.heading', 'Report filters')"
    icon="heroicon-o-adjustments-horizontal"
    collapsible
>
    <style>
        .dth-filter-stack {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .dth-filter-form {
            display: flex;
            flex-direction: column;
            gap: 2.25rem;
        }

        .dth-filter-range,
        .dth-filter-options {
            display: grid;
            gap: 1.5rem;
        }

        .dth-filter-field {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .dth-filter-field .fi-input-wrp {
            margin-top: 0;
        }

        .dth-filter-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 0.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgb(229 231 235 / 0.8);
        }

        .dark .dth-filter-actions {
            border-top-color: rgb(255 255 255 / 0.1);
        }

        .dth-filter-range {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dth-filter-options {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        @media (max-width: 767px) {
            .dth-filter-range,
            .dth-filter-options {
                grid-template-columns: minmax(0, 1fr);
            }

            .dth-filter-actions {
                align-items: stretch;
                flex-direction: column-reverse;
            }
        }
    </style>

    <div class="dth-filter-stack">
        <form method="GET" action="{{ $actionUrl }}" class="dth-filter-form">
            <div class="dth-filter-stack">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="dth-filter-field">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ \Dth\Email\Support\UiText::get('dashboard.filters.range_label', 'Reporting period') }}
                        </div>
                        <div class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                            <span class="text-gray-950 dark:text-white">
                                {{ \Illuminate\Support\Carbon::parse($state['start_date'])->format('d/m/Y') }} - {{ \Illuminate\Support\Carbon::parse($state['end_date'])->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                        @foreach ($presets as $key => $preset)
                            <x-filament::button
                                tag="a"
                                :href="$preset['url']"
                                size="sm"
                                :color="$activePreset === $key ? 'primary' : 'gray'"
                                :outlined="$activePreset !== $key"
                            >
                                {{ $preset['label'] }}
                            </x-filament::button>
                        @endforeach
                    </div>
                </div>

                <div class="dth-filter-range">
                    <div class="dth-filter-field">
                        <label class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ \Dth\Email\Support\UiText::get('dashboard.filters.start_date', 'Start date') }}
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="date"
                                name="start_date"
                                value="{{ $state['start_date'] }}"
                                max="{{ now()->toDateString() }}"
                                required
                            />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="dth-filter-field">
                        <label class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ \Dth\Email\Support\UiText::get('dashboard.filters.end_date', 'End date') }}
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="date"
                                name="end_date"
                                value="{{ $state['end_date'] }}"
                                max="{{ now()->toDateString() }}"
                                required
                            />
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </div>

            <div class="dth-filter-options">
                <div class="dth-filter-field">
                    <label class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ \Dth\Email\Support\UiText::get('dashboard.filters.sending_account', 'Sending account') }}
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select name="sending_account_id">
                            <option value="">
                                {{ \Dth\Email\Support\UiText::get('dashboard.filters.all_accounts', 'All sending accounts') }}
                            </option>
                            @foreach ($sendingAccounts as $id => $name)
                                <option value="{{ $id }}" @selected((string) $state['sending_account_id'] === (string) $id)>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div class="dth-filter-field">
                    <label class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ \Dth\Email\Support\UiText::get('dashboard.filters.campaign_status', 'Campaign status') }}
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select name="campaign_status">
                            <option value="">
                                {{ \Dth\Email\Support\UiText::get('dashboard.filters.all_statuses', 'All statuses') }}
                            </option>
                            @foreach ($campaignStatuses as $value => $label)
                                <option value="{{ $value }}" @selected((string) $state['campaign_status'] === (string) $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div class="dth-filter-field">
                    <label class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ \Dth\Email\Support\UiText::get('dashboard.filters.compare_previous', 'Compare with previous period') }}
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select name="compare_previous">
                            <option value="1" @selected($state['compare_previous'])>
                                {{ \Dth\Email\Support\UiText::get('dashboard.filters.compare_yes', 'Yes') }}
                            </option>
                            <option value="0" @selected(! $state['compare_previous'])>
                                {{ \Dth\Email\Support\UiText::get('dashboard.filters.compare_no', 'No') }}
                            </option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>

            <div class="dth-filter-actions">
                <x-filament::button
                    tag="a"
                    :href="$resetUrl"
                    color="gray"
                    outlined
                    icon="heroicon-o-arrow-path"
                >
                    {{ \Dth\Email\Support\UiText::get('dashboard.filters.reset', 'Reset') }}
                </x-filament::button>

                <x-filament::button type="submit" icon="heroicon-o-funnel">
                    {{ \Dth\Email\Support\UiText::get('dashboard.filters.apply', 'Apply filters') }}
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament::section>
