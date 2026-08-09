<x-filament-panels::page>
    @php
        $a = $analytics;
        $c = $a['current'];
        $money = static fn ($value): string => number_format((float) $value, 0, ',', '.').' ₫';
    @endphp

    <div class="dth-analytics-shell" wire:loading.class="opacity-70">
        <div class="dth-dashboard-hero dth-tone-admin">
            <div>
                <p class="dth-eyebrow">{{ company_name() }}</p>
                <h2>{{ __('analytics.executive_dashboard') }}</h2>
                <p>{{ __('analytics.executive_dashboard_subtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-analytics.period-filter />
                <x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button>
            </div>
        </div>

        <div class="dth-analytics-kpi-grid">
            <x-analytics.kpi-card :label="__('analytics.gross_collected')" :value="$money($c['gross_collected'])" icon="heroicon-o-banknotes" tone="success" :delta="$a['deltas']['gross_collected']" />
            <x-analytics.kpi-card :label="__('analytics.net_revenue')" :value="$money($c['net_revenue'])" icon="heroicon-o-chart-bar-square" tone="warning" :delta="$a['deltas']['net_revenue']" />
            <x-analytics.kpi-card :label="__('analytics.paid_customers')" :value="number_format($c['paid_customers'])" icon="heroicon-o-user-group" tone="info" :delta="$a['deltas']['paid_customers']" />
            <x-analytics.kpi-card :label="__('analytics.new_leads')" :value="number_format($c['new_leads'])" icon="heroicon-o-user-plus" tone="marketing" :delta="$a['deltas']['new_leads']" />
            <x-analytics.kpi-card :label="__('analytics.pending_verification')" :value="$money($c['pending_verification'])" icon="heroicon-o-clock" tone="warning" :helper="__('analytics.current_open_value')" />
            <x-analytics.kpi-card :label="__('analytics.outstanding')" :value="$money($c['outstanding'])" icon="heroicon-o-receipt-percent" tone="danger" :helper="__('analytics.accepted_not_collected')" />
            <x-analytics.kpi-card :label="__('analytics.customer_interactions')" :value="number_format($c['customer_interactions'])" icon="heroicon-o-chat-bubble-left-right" tone="info" :delta="$a['deltas']['customer_interactions']" />
            <x-analytics.kpi-card :label="__('analytics.active_staff')" :value="number_format($workforce['summary']['active_staff'])" icon="heroicon-o-identification" tone="primary" :helper="__('analytics.workforce_scope_helper')" />
        </div>

        <div class="dth-analytics-layout-2">
            <x-filament::section>
                <x-slot name="heading">{{ __('analytics.revenue_trend') }}</x-slot>
                <x-slot name="description">{{ __('analytics.revenue_trend_description') }}</x-slot>
                <x-analytics.line-chart :current="$a['trend']['current']" :previous="$a['trend']['previous']" />
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('analytics.revenue_by_source') }}</x-slot>
                <x-slot name="description">{{ __('analytics.revenue_by_source_description') }}</x-slot>
                <x-analytics.donut-chart :items="$a['sources']" :center-label="__('analytics.net_revenue')" />
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('analytics.management_insights') }}</x-slot>
            <x-slot name="description">{{ __('analytics.management_insights_description') }}</x-slot>
            <x-analytics.insights :items="$a['insights']" />
        </x-filament::section>

        <div class="dth-analytics-layout-equal">
            <x-filament::section>
                <x-slot name="heading">{{ __('analytics.revenue_by_service') }}</x-slot>
                <x-slot name="description">{{ __('analytics.revenue_by_service_description') }}</x-slot>
                <x-analytics.bar-chart :items="$a['services']" tone="sales" />
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('analytics.campaign_efficiency') }}</x-slot>
                <x-slot name="description">{{ __('analytics.campaign_efficiency_description') }}</x-slot>
                <div class="dth-campaign-card-grid">
                    @forelse($a['campaigns'] as $row)
                        <article class="dth-campaign-card">
                            <div class="dth-campaign-card__head"><strong>{{ $row['name'] }}</strong><x-filament::badge :color="($row['roas'] ?? 0) >= 1 ? 'success' : 'warning'">ROAS {{ $row['roas'] === null ? '—' : number_format($row['roas'], 2).'x' }}</x-filament::badge></div>
                            <div class="dth-campaign-card__metrics">
                                <div><span>{{ __('analytics.leads') }}</span><strong>{{ number_format($row['leads']) }}</strong></div>
                                <div><span>{{ __('analytics.paid_customers') }}</span><strong>{{ number_format($row['paid_customers']) }}</strong></div>
                                <div><span>{{ __('analytics.net_revenue') }}</span><strong>{{ number_format($row['net_revenue']/1000000, 1) }}M</strong></div>
                            </div>
                        </article>
                    @empty
                        <div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('analytics.workforce_snapshot') }}</x-slot>
            <x-slot name="description">{{ __('analytics.workforce_snapshot_description') }}</x-slot>
            <div class="overflow-x-auto">
                <table class="dth-analytics-table">
                    <thead><tr><th>{{ __('field.staff') }}</th><th>{{ __('field.department') }}</th><th>{{ __('analytics.activity_index') }}</th><th>{{ __('analytics.primary_output') }}</th><th>{{ __('analytics.business_impact') }}</th><th>{{ __('analytics.highlight') }}</th></tr></thead>
                    <tbody>
                    @forelse($workforce['top_staff'] as $row)
                        <tr>
                            <td><div class="font-semibold">{{ $row['name'] }}</div><div class="text-xs text-gray-500">{{ $row['employee_code'] }} · {{ $row['position'] }}</div></td>
                            <td>{{ $row['department'] }}</td>
                            <td><div class="dth-analytics-index"><div class="dth-analytics-index__track"><span style="width: {{ $row['activity_index'] }}%"></span></div><strong>{{ number_format($row['activity_index'], 0) }}</strong></div></td>
                            <td><strong>{{ $row['metric_1_value'] }}</strong><div class="text-xs text-gray-500">{{ $row['metric_1_label'] }}</div></td>
                            <td>@if(in_array($row['department_key'], ['sales','marketing','finance'], true))<strong>{{ $money($row['impact_value']) }}</strong>@else<strong>{{ number_format($row['customers']) }}</strong><div class="text-xs text-gray-500">{{ __('analytics.customers') }}</div>@endif</td>
                            <td class="max-w-sm text-gray-600 dark:text-gray-300">{{ $row['highlight'] }}</td>
                        </tr>
                    @empty<tr><td colspan="6" class="text-center text-gray-500">{{ __('uiux.dashboard.common.no_data') }}</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                @if($links['workforce'])<x-filament::button tag="a" :href="$links['workforce']" color="gray" icon="heroicon-o-users">{{ __('analytics.open_workforce_analysis') }}</x-filament::button>@endif
                @if($links['campaigns'])<x-filament::button tag="a" :href="$links['campaigns']" color="gray" icon="heroicon-o-megaphone">{{ __('analytics.open_campaign_analysis') }}</x-filament::button>@endif
                @if($links['revenue'])<x-filament::button tag="a" :href="$links['revenue']" color="gray" icon="heroicon-o-chart-bar">{{ __('analytics.open_revenue_analysis') }}</x-filament::button>@endif
                @if($links['audit'])<x-filament::button tag="a" :href="$links['audit']" color="gray" icon="heroicon-o-clipboard-document-check">{{ __('uiux.audit.title') }}</x-filament::button>@endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
