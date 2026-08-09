<x-filament-panels::page>
    <div class="space-y-6">
        <div class="dth-dashboard-hero dth-tone-marketing">
            <div>
                <p class="dth-eyebrow">{{ $isManager ? __('uiux.dashboard.common.manager_scope') : __('uiux.dashboard.common.personal_scope') }}</p>
                <h2>{{ $isManager ? __('uiux.dashboard.marketing.title') : __('uiux.dashboard.marketing.my_work') }}</h2>
                <p>{{ __('uiux.dashboard.marketing.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($isManager)<x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button>@endif
                @if($links['campaigns'])
                    <x-filament::button tag="a" :href="$links['campaigns']" color="warning" icon="heroicon-o-megaphone">
                        {{ __('resource.marketing_campaign.plural') }}
                    </x-filament::button>
                @endif
            </div>
        </div>

        <div class="dth-report-grid dth-report-grid-4">
            @foreach([
                [__('uiux.dashboard.marketing.active_campaigns'), $summary['active_campaigns'], 'heroicon-o-megaphone', 'warning'],
                [__('uiux.dashboard.marketing.landing_pages'), $summary['landing_pages'], 'heroicon-o-window', 'info'],
                [__('uiux.dashboard.marketing.submissions'), $summary['submissions'], 'heroicon-o-inbox-arrow-down', 'primary'],
                [__('uiux.dashboard.marketing.generated_leads'), $summary['leads'], 'heroicon-o-user-plus', 'success'],
                [__('uiux.dashboard.marketing.paid_customers'), $summary['paid_customers'], 'heroicon-o-user-group', 'success'],
                [__('uiux.dashboard.marketing.attributed_revenue'), number_format($summary['revenue']).' VND', 'heroicon-o-banknotes', 'success'],
            ] as [$label,$value,$icon,$tone])
                <div class="dth-metric-card" data-tone="{{ $tone }}">
                    <div class="flex items-center justify-between gap-3"><span class="dth-metric-label">{{ $label }}</span><x-filament::icon :icon="$icon" class="h-5 w-5" /></div>
                    <div class="dth-metric-value">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">{{ __('uiux.dashboard.marketing.source_performance') }}</x-slot>
                <x-slot name="description">{{ __('uiux.report.description') }}</x-slot>
                <div class="space-y-4">
                    @php($max = max(1, collect($sourceRows)->max('value') ?: 1))
                    @forelse($sourceRows as $row)
                        <div class="dth-bar-row">
                            <div class="dth-bar-meta"><span>{{ $row['label'] }}</span><strong>{{ number_format($row['value']) }} VND</strong></div>
                            <div class="dth-bar-track"><span class="dth-bar-fill dth-bar-marketing" style="width: {{ max(3, ($row['value'] / $max) * 100) }}%"></span></div>
                        </div>
                    @empty
                        <div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('uiux.dashboard.marketing.top_landing_pages') }}</x-slot>
                <div class="space-y-3">
                    @forelse($topLandingPages as $row)
                        <div class="dth-list-row"><span>{{ $row['name'] }}</span><x-filament::badge color="info">{{ number_format($row['total']) }}</x-filament::badge></div>
                    @empty
                        <div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('uiux.dashboard.common.reports') }}</x-slot>
            <div class="flex flex-wrap gap-3">
                @if($links['campaign_report'])<x-filament::button tag="a" :href="$links['campaign_report']" color="gray" icon="heroicon-o-chart-bar">{{ __('uiux.report.email_performance') }}</x-filament::button>@endif
                @if($links['revenue_report'])<x-filament::button tag="a" :href="$links['revenue_report']" color="gray" icon="heroicon-o-banknotes">{{ __('uiux.report.revenue') }}</x-filament::button>@endif
                @if($links['landing_pages'])<x-filament::button tag="a" :href="$links['landing_pages']" color="gray" icon="heroicon-o-window">{{ __('resource.landing_page.plural') }}</x-filament::button>@endif
                @if($links['leads'])<x-filament::button tag="a" :href="$links['leads']" color="gray" icon="heroicon-o-user-group">{{ __('resource.lead.plural') }}</x-filament::button>@endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
