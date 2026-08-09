<x-filament-panels::page>
    @php($a=$analytics; $s=$a['summary']; $money=static fn($v)=>number_format((float)$v,0,',','.').' ₫')
    <div class="dth-analytics-shell" wire:loading.class="opacity-70">
        <div class="dth-dashboard-hero dth-tone-finance"><div><p class="dth-eyebrow">{{ __('navigation.group.finance') }}</p><h2>{{ __('uiux.dashboard.finance.title') }}</h2><p>{{ __('analytics.finance_dashboard_subtitle') }}</p></div><div class="flex flex-wrap items-center gap-2"><x-analytics.period-filter /><x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button></div></div>

        <div class="dth-analytics-kpi-grid">
            <x-analytics.kpi-card :label="__('analytics.gross_collected')" :value="$money($s['gross_collected'])" icon="heroicon-o-banknotes" tone="success" :delta="$a['deltas']['gross_collected']" />
            <x-analytics.kpi-card :label="__('analytics.net_revenue')" :value="$money($s['net_revenue'])" icon="heroicon-o-chart-bar" tone="success" :delta="$a['deltas']['net_revenue']" />
            <x-analytics.kpi-card :label="__('analytics.tax')" :value="$money($s['tax'])" icon="heroicon-o-receipt-percent" tone="info" />
            <x-analytics.kpi-card :label="__('analytics.payments')" :value="number_format($s['payments'])" icon="heroicon-o-credit-card" tone="info" :delta="$a['deltas']['payments']" />
            <x-analytics.kpi-card :label="__('analytics.pending_verification')" :value="$money($s['pending_verification'])" icon="heroicon-o-clock" tone="warning" />
            <x-analytics.kpi-card :label="__('analytics.outstanding')" :value="$money($s['outstanding'])" icon="heroicon-o-exclamation-triangle" tone="danger" />
            <x-analytics.kpi-card :label="__('analytics.average_payment')" :value="$money($s['average_payment'])" icon="heroicon-o-scale" tone="primary" />
            <x-analytics.kpi-card :label="__('analytics.avg_verification_time')" :value="number_format($s['avg_verification_minutes'],1).' '.__('analytics.minutes')" icon="heroicon-o-bolt" tone="warning" />
        </div>

        <div class="dth-analytics-layout-2">
            <x-filament::section><x-slot name="heading">{{ __('analytics.cashflow_comparison') }}</x-slot><x-slot name="description">{{ __('analytics.cashflow_comparison_description') }}</x-slot><x-analytics.line-chart :current="$a['trend']['current']" :previous="$a['trend']['previous']" /></x-filament::section>
            <x-filament::section><x-slot name="heading">{{ __('analytics.outstanding_aging') }}</x-slot><x-slot name="description">{{ __('analytics.outstanding_aging_description') }}</x-slot><x-analytics.bar-chart :items="$a['aging']" tone="finance" /></x-filament::section>
        </div>

        <div class="dth-analytics-layout-equal">
            <x-filament::section><x-slot name="heading">{{ __('analytics.cash_by_source') }}</x-slot><x-analytics.donut-chart :items="$a['sources']" :center-label="__('analytics.net_revenue')" /></x-filament::section>
            <x-filament::section>
                <x-slot name="heading">{{ __('analytics.reconciliation_queue') }}</x-slot><x-slot name="description">{{ __('analytics.reconciliation_queue_description') }}</x-slot>
                <div class="space-y-2">@forelse($queue as $row)<div class="dth-list-row"><div><div class="font-semibold">{{ $row['code'] }} · {{ $row['customer'] }}</div><div class="text-xs text-gray-500">{{ $row['submitted_at']?->format('d/m/Y H:i') ?: '—' }}</div></div><div class="text-right"><div class="font-semibold">{{ $money($row['amount']) }}</div></div></div>@empty<div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>@endforelse</div>
            </x-filament::section>
        </div>

        <x-filament::section><x-slot name="heading">{{ __('analytics.finance_actions') }}</x-slot><div class="flex flex-wrap gap-2">@if($links['tracking'])<x-filament::button tag="a" :href="$links['tracking']" color="warning" icon="heroicon-o-check-circle">{{ __('finance.payment_tracking') }}</x-filament::button>@endif @if($links['history'])<x-filament::button tag="a" :href="$links['history']" color="gray" icon="heroicon-o-clock">{{ __('finance.payment_history') }}</x-filament::button>@endif @if($links['revenue'])<x-filament::button tag="a" :href="$links['revenue']" color="gray" icon="heroicon-o-chart-bar">{{ __('analytics.open_revenue_analysis') }}</x-filament::button>@endif</div></x-filament::section>
    </div>
</x-filament-panels::page>
