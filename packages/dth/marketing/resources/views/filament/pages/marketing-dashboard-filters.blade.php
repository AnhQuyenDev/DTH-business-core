<x-filament::section
    :heading="\Dth\Marketing\Support\UiText::get('analytics.filters.heading', 'Report filters')"
    icon="heroicon-o-adjustments-horizontal"
    collapsible
>
    <style>
        .dth-mkt-filter { display:flex; flex-direction:column; gap:1.4rem; }
        .dth-mkt-filter-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; }
        .dth-mkt-filter-field { display:flex; flex-direction:column; gap:.45rem; }
        .dth-mkt-filter-actions { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding-top:1rem; border-top:1px solid rgb(229 231 235 / .8); }
        .dark .dth-mkt-filter-actions { border-top-color:rgb(255 255 255 / .1); }
        @media (max-width:1024px){ .dth-mkt-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr));} }
        @media (max-width:640px){ .dth-mkt-filter-grid{grid-template-columns:1fr;} .dth-mkt-filter-actions{flex-direction:column-reverse;align-items:stretch;} }
    </style>

    <form method="GET" action="{{ $actionUrl }}" class="dth-mkt-filter">
        <div style="display:flex;gap:.7rem;flex-wrap:wrap;align-items:center;justify-content:space-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ \Dth\Marketing\Support\UiText::get('analytics.filters.period', 'Reporting period') }}
                </div>
                <div class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                    {{ $filter->start->format('d/m/Y') }} - {{ $filter->end->format('d/m/Y') }}
                </div>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                @foreach($presets as $key => $preset)
                    <x-filament::button
                        tag="a"
                        :href="$preset['url']"
                        size="sm"
                        :color="$state['period'] === $key ? 'primary' : 'gray'"
                        :outlined="$state['period'] !== $key"
                    >{{ $preset['label'] }}</x-filament::button>
                @endforeach
            </div>
        </div>

        <input type="hidden" name="period" value="custom">

        <div class="dth-mkt-filter-grid">
            <div class="dth-mkt-filter-field">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.start', 'Start date') }}</label>
                <x-filament::input.wrapper><x-filament::input type="date" name="start" value="{{ $state['start'] }}" max="{{ now()->toDateString() }}" required /></x-filament::input.wrapper>
            </div>
            <div class="dth-mkt-filter-field">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.end', 'End date') }}</label>
                <x-filament::input.wrapper><x-filament::input type="date" name="end" value="{{ $state['end'] }}" max="{{ now()->toDateString() }}" required /></x-filament::input.wrapper>
            </div>
            <div class="dth-mkt-filter-field">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.campaign', 'Campaign') }}</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select name="campaign">
                        <option value="">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.all_campaigns', 'All campaigns') }}</option>
                        @foreach($campaigns as $id => $name)<option value="{{ $id }}" @selected((string)$state['campaign'] === (string)$id)>{{ $name }}</option>@endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
            <div class="dth-mkt-filter-field">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.status', 'Campaign status') }}</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select name="status">
                        <option value="">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.all_statuses', 'All statuses') }}</option>
                        @foreach($statuses as $value => $label)<option value="{{ $value }}" @selected((string)$state['status'] === (string)$value)>{{ $label }}</option>@endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
            <div class="dth-mkt-filter-field">
                <label class="text-sm font-medium text-gray-950 dark:text-white">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.source', 'Source') }}</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select name="source">
                        <option value="">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.all_sources', 'All sources') }}</option>
                        @foreach($sources as $source)<option value="{{ $source }}" @selected((string)$state['source'] === (string)$source)>{{ $source }}</option>@endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>

        <div class="dth-mkt-filter-actions">
            <x-filament::button tag="a" :href="$resetUrl" color="gray" outlined icon="heroicon-o-arrow-path">
                {{ \Dth\Marketing\Support\UiText::get('analytics.filters.reset', 'Reset') }}
            </x-filament::button>
            <x-filament::button type="submit" icon="heroicon-o-funnel">
                {{ \Dth\Marketing\Support\UiText::get('analytics.filters.apply', 'Apply filters') }}
            </x-filament::button>
        </div>
    </form>
</x-filament::section>
