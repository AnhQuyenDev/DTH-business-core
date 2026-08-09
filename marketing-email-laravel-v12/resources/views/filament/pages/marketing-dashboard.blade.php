<x-filament-panels::page>
    @php
        $a = $analytics;
        $s = $a['summary'];
        $money = static fn ($v) => number_format((float) $v, 0, ',', '.').' ₫';
    @endphp
    <div class="dth-analytics-shell" wire:loading.class="opacity-70">
        <div class="dth-dashboard-hero dth-tone-marketing">
            <div><p class="dth-eyebrow">{{ $isManager ? __('uiux.dashboard.common.manager_scope') : __('uiux.dashboard.common.personal_scope') }}</p><h2>{{ $isManager ? __('uiux.dashboard.marketing.title') : __('uiux.dashboard.marketing.my_work') }}</h2><p>{{ __('analytics.marketing_dashboard_subtitle') }}</p></div>
            <div class="flex flex-wrap items-center gap-2"><x-analytics.period-filter />@if($isManager)<x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button>@endif</div>
        </div>

        <div class="dth-analytics-kpi-grid">
            <x-analytics.kpi-card :label="__('analytics.landing_views')" :value="number_format($s['views'])" icon="heroicon-o-eye" tone="info" :delta="$a['deltas']['views']" />
            <x-analytics.kpi-card :label="__('analytics.submissions')" :value="number_format($s['submissions'])" icon="heroicon-o-inbox-arrow-down" tone="marketing" :delta="$a['deltas']['submissions']" />
            <x-analytics.kpi-card :label="__('analytics.leads_generated')" :value="number_format($s['leads'])" icon="heroicon-o-user-plus" tone="warning" :delta="$a['deltas']['leads']" />
            <x-analytics.kpi-card :label="__('analytics.attributed_revenue')" :value="$money($s['net_revenue'])" icon="heroicon-o-banknotes" tone="success" :delta="$a['deltas']['net_revenue']" />
            <x-analytics.kpi-card :label="__('analytics.paid_customers')" :value="number_format($s['paid_customers'])" icon="heroicon-o-user-group" tone="success" :delta="$a['deltas']['paid_customers']" />
            <x-analytics.kpi-card :label="__('analytics.roas')" :value="$s['roas'] === null ? '—' : number_format($s['roas'],2).'x'" icon="heroicon-o-arrow-trending-up" tone="marketing" :helper="__('analytics.budget').': '.$money($s['budget'])" />
            <x-analytics.kpi-card :label="__('analytics.view_to_submission')" :value="number_format($s['view_to_submission'],1).'%'" icon="heroicon-o-cursor-arrow-rays" tone="info" />
            <x-analytics.kpi-card :label="__('analytics.lead_to_paid')" :value="number_format($s['lead_to_paid'],1).'%'" icon="heroicon-o-check-badge" tone="success" />
        </div>

        <div class="dth-analytics-layout-2">
            <x-filament::section><x-slot name="heading">{{ __('analytics.marketing_funnel') }}</x-slot><x-slot name="description">{{ __('analytics.marketing_funnel_description') }}</x-slot><x-analytics.funnel :items="$a['funnel']" /></x-filament::section>
            <x-filament::section><x-slot name="heading">{{ __('analytics.revenue_by_source') }}</x-slot><x-analytics.donut-chart :items="$a['sources']" :center-label="__('analytics.attributed_revenue')" /></x-filament::section>
        </div>

        <x-filament::section><x-slot name="heading">{{ __('analytics.attributed_revenue_trend') }}</x-slot><x-analytics.line-chart :current="$a['trend']['current']" :previous="$a['trend']['previous']" /></x-filament::section>

        <div class="dth-analytics-layout-equal">
            <x-filament::section>
                <x-slot name="heading">{{ __('analytics.marketing_campaign_comparison') }}</x-slot>
                <div class="dth-campaign-card-grid">
                    @forelse($a['campaigns'] as $row)<article class="dth-campaign-card"><div class="dth-campaign-card__head"><strong>{{ $row['name'] }}</strong><x-filament::badge :color="($row['roas'] ?? 0)>=1?'success':'warning'">ROAS {{ $row['roas']===null?'—':number_format($row['roas'],2).'x' }}</x-filament::badge></div><div class="dth-campaign-card__metrics"><div><span>{{ __('analytics.leads') }}</span><strong>{{ $row['leads'] }}</strong></div><div><span>{{ __('analytics.paid_customers') }}</span><strong>{{ $row['paid_customers'] }}</strong></div><div><span>{{ __('analytics.net_revenue') }}</span><strong>{{ number_format($row['net_revenue']/1000000,1) }}M</strong></div></div></article>@empty<div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>@endforelse
                </div>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">{{ __('analytics.email_campaign_performance') }}</x-slot>
                <div class="overflow-x-auto"><table class="dth-analytics-table"><thead><tr><th>{{ __('field.name') }}</th><th class="numeric">{{ __('analytics.open_rate') }}</th><th class="numeric">{{ __('analytics.click_rate') }}</th><th class="numeric">{{ __('analytics.paid_customers') }}</th><th class="numeric">{{ __('analytics.net_revenue') }}</th></tr></thead><tbody>@forelse($a['email_campaigns'] as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td class="numeric">{{ $row['open_rate'] }}%</td><td class="numeric">{{ $row['click_rate'] }}%</td><td class="numeric">{{ $row['paid_customers'] }}</td><td class="numeric">{{ $money($row['net_revenue']) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-gray-500">{{ __('uiux.dashboard.common.no_data') }}</td></tr>@endforelse</tbody></table></div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('analytics.landing_page_performance') }}</x-slot>
            <div class="overflow-x-auto"><table class="dth-analytics-table"><thead><tr><th>{{ __('resource.landing_page.singular') }}</th><th class="numeric">{{ __('analytics.views') }}</th><th class="numeric">{{ __('analytics.submissions') }}</th><th class="numeric">{{ __('analytics.leads') }}</th><th class="numeric">{{ __('analytics.paid_customers') }}</th><th class="numeric">{{ __('analytics.net_revenue') }}</th></tr></thead><tbody>@forelse($a['landing_pages'] as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td class="numeric">{{ $row['views'] }}</td><td class="numeric">{{ $row['submissions'] }}</td><td class="numeric">{{ $row['leads'] }}</td><td class="numeric">{{ $row['paid_customers'] }}</td><td class="numeric">{{ $money($row['net_revenue']) }}</td></tr>@empty<tr><td colspan="6" class="text-center text-gray-500">{{ __('uiux.dashboard.common.no_data') }}</td></tr>@endforelse</tbody></table></div>
            <div class="mt-4 flex flex-wrap gap-2">@if($links['campaign_analysis'])<x-filament::button tag="a" :href="$links['campaign_analysis']" color="gray" icon="heroicon-o-presentation-chart-line">{{ __('analytics.open_campaign_analysis') }}</x-filament::button>@endif @if($links['revenue'])<x-filament::button tag="a" :href="$links['revenue']" color="gray" icon="heroicon-o-chart-bar">{{ __('analytics.open_revenue_analysis') }}</x-filament::button>@endif @if($links['workforce'])<x-filament::button tag="a" :href="$links['workforce']" color="gray" icon="heroicon-o-users">{{ __('analytics.open_workforce_analysis') }}</x-filament::button>@endif</div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
