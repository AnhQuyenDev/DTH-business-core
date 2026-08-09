<x-filament-panels::page>
    @php
        $a = $analytics;
        $s = $a['summary'];
        $money = static fn ($v) => number_format((float) $v, 0, ',', '.').' ₫';
    @endphp
    <div class="dth-analytics-shell" wire:loading.class="opacity-70">
        <div class="dth-dashboard-hero dth-tone-sales"><div><p class="dth-eyebrow">{{ $isManager ? __('uiux.dashboard.common.manager_scope') : __('uiux.dashboard.common.personal_scope') }}</p><h2>{{ $isManager ? __('analytics.sales_performance_dashboard') : __('analytics.my_sales_performance') }}</h2><p>{{ __('analytics.sales_dashboard_subtitle') }}</p></div><div class="flex flex-wrap items-center gap-2"><x-analytics.period-filter />@if($isManager)<x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button>@endif</div></div>

        <div class="dth-analytics-kpi-grid">
            <x-analytics.kpi-card :label="__('analytics.sent_quotations')" :value="number_format($s['sent_quotations'])" icon="heroicon-o-paper-airplane" tone="info" />
            <x-analytics.kpi-card :label="__('analytics.accepted_quotations')" :value="number_format($s['accepted_quotations'])" icon="heroicon-o-check-badge" tone="success" :delta="$a['deltas']['accepted_quotations']" />
            <x-analytics.kpi-card :label="__('analytics.acceptance_rate')" :value="number_format($s['acceptance_rate'],1).'%'" icon="heroicon-o-arrow-trending-up" tone="warning" />
            <x-analytics.kpi-card :label="__('analytics.net_revenue')" :value="$money($s['net_revenue'])" icon="heroicon-o-banknotes" tone="success" :delta="$a['deltas']['net_revenue']" />
            <x-analytics.kpi-card :label="__('analytics.paid_customers')" :value="number_format($s['paid_customers'])" icon="heroicon-o-user-group" tone="info" :delta="$a['deltas']['paid_customers']" />
            <x-analytics.kpi-card :label="__('analytics.average_payment')" :value="$money($s['average_payment'])" icon="heroicon-o-scale" tone="primary" />
        </div>

        <div class="dth-analytics-layout-2">
            <x-filament::section><x-slot name="heading">{{ __('analytics.sales_conversion_funnel') }}</x-slot><x-slot name="description">{{ __('analytics.sales_conversion_funnel_description') }}</x-slot><x-analytics.funnel :items="$a['funnel']" /></x-filament::section>
            <x-filament::section><x-slot name="heading">{{ __('analytics.revenue_by_service') }}</x-slot><x-analytics.bar-chart :items="$a['services']" tone="sales" /></x-filament::section>
        </div>

        <x-filament::section><x-slot name="heading">{{ __('analytics.sales_revenue_trend') }}</x-slot><x-analytics.line-chart :current="$a['trend']['current']" :previous="$a['trend']['previous']" /></x-filament::section>

        @if($isManager)
            <x-filament::section><x-slot name="heading">{{ __('analytics.sales_team_performance') }}</x-slot><x-slot name="description">{{ __('analytics.sales_team_performance_description') }}</x-slot><x-analytics.bar-chart :items="$a['sales_staff']" tone="sales" /></x-filament::section>
        @endif

        <x-filament::section><x-slot name="heading">{{ __('analytics.sales_actions') }}</x-slot><div class="flex flex-wrap gap-2">@if($links['quotations'])<x-filament::button tag="a" :href="$links['quotations']" color="warning" icon="heroicon-o-document-text">{{ __('resource.quotation.plural') }}</x-filament::button>@endif @if($links['approvals'])<x-filament::button tag="a" :href="$links['approvals']" color="gray" icon="heroicon-o-shield-check">{{ __('navigation.quotation_approvals') }}</x-filament::button>@endif @if($links['revenue'])<x-filament::button tag="a" :href="$links['revenue']" color="gray" icon="heroicon-o-chart-bar">{{ __('analytics.open_revenue_analysis') }}</x-filament::button>@endif @if($links['workforce'])<x-filament::button tag="a" :href="$links['workforce']" color="gray" icon="heroicon-o-users">{{ __('analytics.open_workforce_analysis') }}</x-filament::button>@endif</div></x-filament::section>
    </div>
</x-filament-panels::page>
