<x-filament::section
    :heading="\Dth\Email\Support\UiText::get('dashboard.filters.heading', 'Dashboard filters')"
    :description="\Dth\Email\Support\UiText::get('dashboard.filters.description', 'Filters use a normal page request so the report remains stable and the URL can be bookmarked or shared.')"
    icon="heroicon-o-funnel"
    collapsible
>
    <form method="GET" action="{{ $actionUrl }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
        <div>
            <label class="text-sm font-medium text-gray-950 dark:text-white">
                {{ \Dth\Email\Support\UiText::get('dashboard.filters.start_date', 'Start date') }}
            </label>

            <x-filament::input.wrapper class="mt-2">
                <x-filament::input
                    type="date"
                    name="start_date"
                    value="{{ $state['start_date'] }}"
                    max="{{ now()->toDateString() }}"
                    required
                />
            </x-filament::input.wrapper>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-950 dark:text-white">
                {{ \Dth\Email\Support\UiText::get('dashboard.filters.end_date', 'End date') }}
            </label>

            <x-filament::input.wrapper class="mt-2">
                <x-filament::input
                    type="date"
                    name="end_date"
                    value="{{ $state['end_date'] }}"
                    max="{{ now()->toDateString() }}"
                    required
                />
            </x-filament::input.wrapper>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-950 dark:text-white">
                {{ \Dth\Email\Support\UiText::get('dashboard.filters.sending_account', 'Sending account') }}
            </label>

            <x-filament::input.wrapper class="mt-2">
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

        <div>
            <label class="text-sm font-medium text-gray-950 dark:text-white">
                {{ \Dth\Email\Support\UiText::get('dashboard.filters.campaign_status', 'Campaign status') }}
            </label>

            <x-filament::input.wrapper class="mt-2">
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

        <div>
            <label class="text-sm font-medium text-gray-950 dark:text-white">
                {{ \Dth\Email\Support\UiText::get('dashboard.filters.compare_previous', 'Compare with previous period') }}
            </label>

            <x-filament::input.wrapper class="mt-2">
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

        <div class="flex items-end gap-2">
            <x-filament::button type="submit" icon="heroicon-o-funnel">
                {{ \Dth\Email\Support\UiText::get('dashboard.filters.apply', 'Apply filters') }}
            </x-filament::button>

            <x-filament::button
                tag="a"
                :href="$resetUrl"
                color="gray"
                outlined
                icon="heroicon-o-arrow-path"
            >
                {{ \Dth\Email\Support\UiText::get('dashboard.filters.reset', 'Reset') }}
            </x-filament::button>
        </div>
    </form>
</x-filament::section>
