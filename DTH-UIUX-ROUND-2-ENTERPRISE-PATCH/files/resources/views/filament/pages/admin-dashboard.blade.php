<x-filament-panels::page>
    <div class="space-y-6">
        <div class="dth-dashboard-hero dth-tone-admin">
            <div><p class="dth-eyebrow">{{ company_name() }}</p><h2>{{ __('uiux.dashboard.admin.title') }}</h2><p>{{ __('uiux.dashboard.admin.subtitle') }}</p></div>
            <div class="flex flex-wrap gap-2">
                <x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button>
                @if($links['audit'])<x-filament::button tag="a" :href="$links['audit']" color="gray" icon="heroicon-o-clipboard-document-check">{{ __('uiux.audit.title') }}</x-filament::button>@endif
            </div>
        </div>

        <div class="dth-report-grid dth-report-grid-4">
            @foreach([
                [__('uiux.dashboard.admin.new_leads'), $summary['new_leads'], 'heroicon-o-user-plus', 'info', false],
                [__('uiux.dashboard.admin.open_opportunities'), $summary['open_opportunities'], 'heroicon-o-briefcase', 'warning', false],
                [__('uiux.dashboard.admin.cash_collected'), $summary['cash_collected'], 'heroicon-o-banknotes', 'success', true],
                [__('uiux.dashboard.admin.customers'), $summary['customers'], 'heroicon-o-user-group', 'success', false],
                [__('uiux.dashboard.admin.pending_payments'), $summary['pending_payments'], 'heroicon-o-clock', 'warning', false],
                [__('uiux.dashboard.admin.active_campaigns'), $summary['active_campaigns'], 'heroicon-o-megaphone', 'primary', false],
                [__('uiux.dashboard.admin.unassigned_leads'), $summary['unassigned_leads'], 'heroicon-o-user-minus', 'danger', false],
            ] as [$label,$value,$icon,$tone,$money])
                <div class="dth-metric-card" data-tone="{{ $tone }}"><div class="flex items-center justify-between gap-3"><span class="dth-metric-label">{{ $label }}</span><x-filament::icon :icon="$icon" class="h-5 w-5" /></div><div class="dth-metric-value">{{ $money ? number_format($value).' VND' : number_format($value) }}</div></div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">{{ __('uiux.dashboard.admin.funnel') }}</x-slot>
                <div class="space-y-3">
                    @php($maxFunnel = max(1, collect($funnel)->max('value') ?: 1))
                    @foreach($funnel as $row)<div class="dth-bar-row"><div class="dth-bar-meta"><span>{{ $row['label'] }}</span><strong>{{ number_format($row['value']) }}</strong></div><div class="dth-bar-track"><span class="dth-bar-fill dth-bar-admin" style="width: {{ max(3, ($row['value']/$maxFunnel)*100) }}%"></span></div></div>@endforeach
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('uiux.dashboard.admin.revenue_trend') }}</x-slot>
                <div class="dth-column-chart">
                    @php($maxRevenue = max(1, collect($cashflow)->max('value') ?: 1))
                    @foreach($cashflow as $row)<div class="dth-column-item"><div class="dth-column-value">{{ $row['value'] > 0 ? number_format($row['value']/1000000,1).__('uiux.dashboard.common.million_short') : '0' }}</div><div class="dth-column-track"><span class="dth-column-fill dth-bar-finance" style="height: {{ $row['value'] > 0 ? max(8, ($row['value']/$maxRevenue)*100) : 2 }}%"></span></div><div class="dth-column-label">{{ $row['label'] }}</div></div>@endforeach
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('uiux.dashboard.admin.quick_reports') }}</x-slot>
            <x-slot name="description">{{ __('uiux.report.description') }}</x-slot>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                @if($links['campaign_report'])<a href="{{ $links['campaign_report'] }}" class="dth-report-link-card"><x-filament::icon icon="heroicon-o-envelope-open" class="h-5 w-5"/><span>{{ __('uiux.report.email_performance') }}</span><x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4"/></a>@endif
                @if($links['revenue_report'])<a href="{{ $links['revenue_report'] }}" class="dth-report-link-card"><x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5"/><span>{{ __('uiux.report.revenue') }}</span><x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4"/></a>@endif
                @if($links['leads'])<a href="{{ $links['leads'] }}" class="dth-report-link-card"><x-filament::icon icon="heroicon-o-user-group" class="h-5 w-5"/><span>{{ __('resource.lead.plural') }}</span><x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4"/></a>@endif
                @if($links['opportunities'])<a href="{{ $links['opportunities'] }}" class="dth-report-link-card"><x-filament::icon icon="heroicon-o-briefcase" class="h-5 w-5"/><span>{{ __('resource.opportunity.plural') }}</span><x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4"/></a>@endif
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament-widgets::widgets :columns="1" :data="$this->getWidgetData()" :widgets="[\App\Filament\Widgets\DashboardSystemAlertsWidget::class]" />
            <x-filament-widgets::widgets :columns="1" :data="$this->getWidgetData()" :widgets="[\App\Filament\Widgets\DashboardStaffWorkloadWidget::class]" />
        </div>
    </div>
</x-filament-panels::page>
