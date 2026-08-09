<x-filament-panels::page>
    <div class="space-y-6">
        <div class="dth-dashboard-hero dth-tone-finance">
            <div><p class="dth-eyebrow">{{ __('navigation.group.finance') }}</p><h2>{{ __('uiux.dashboard.finance.title') }}</h2><p>{{ __('uiux.dashboard.finance.subtitle') }}</p></div>
            <div class="flex flex-wrap gap-2">
                <x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button>
                @if($links['tracking'])<x-filament::button tag="a" :href="$links['tracking']" color="success" icon="heroicon-o-shield-check">{{ __('uiux.dashboard.finance.pending') }}</x-filament::button>@endif
            </div>
        </div>

        <div class="dth-report-grid dth-report-grid-4">
            @foreach([
                [__('uiux.dashboard.finance.pending'), $summary['pending'], 'heroicon-o-clock', 'warning', false],
                [__('uiux.dashboard.finance.paid_today'), $summary['paid_today'], 'heroicon-o-banknotes', 'success', true],
                [__('uiux.dashboard.finance.month_revenue'), $summary['month_revenue'], 'heroicon-o-chart-bar-square', 'success', true],
                [__('uiux.dashboard.finance.receipts'), $summary['receipts'], 'heroicon-o-document-check', 'info', false],
                [__('uiux.dashboard.finance.accepted_unpaid'), $summary['accepted_unpaid'], 'heroicon-o-exclamation-circle', 'warning', false],
            ] as [$label,$value,$icon,$tone,$money])
                <div class="dth-metric-card" data-tone="{{ $tone }}"><div class="flex items-center justify-between"><span class="dth-metric-label">{{ $label }}</span><x-filament::icon :icon="$icon" class="h-5 w-5" /></div><div class="dth-metric-value">{{ $money ? number_format($value).' VND' : number_format($value) }}</div></div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">{{ __('uiux.dashboard.finance.cashflow') }}</x-slot>
                <div class="dth-column-chart">
                    @php($max = max(1, collect($cashflow)->max('value') ?: 1))
                    @foreach($cashflow as $row)
                        <div class="dth-column-item"><div class="dth-column-value">{{ $row['value'] > 0 ? number_format($row['value']/1000000, 1).__('uiux.dashboard.common.million_short') : '0' }}</div><div class="dth-column-track"><span class="dth-column-fill dth-bar-finance" style="height: {{ $row['value'] > 0 ? max(8, ($row['value']/$max)*100) : 2 }}%"></span></div><div class="dth-column-label">{{ $row['label'] }}</div></div>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('uiux.dashboard.finance.reconciliation_queue') }}</x-slot>
                <div class="space-y-3">
                    @forelse($queue as $row)
                        <div class="dth-list-row"><div><div class="font-semibold text-gray-950 dark:text-white">{{ $row['code'] }} · {{ $row['customer'] }}</div><div class="text-xs text-gray-500">{{ $row['submitted_at']?->format('d/m/Y H:i') ?: '—' }}</div></div><div class="text-right"><div class="font-semibold">{{ number_format($row['amount']) }} VND</div>@if($links['tracking'])<a href="{{ $links['tracking'] }}" class="text-xs font-semibold text-primary-600 hover:underline">{{ __('action.view') }}</a>@endif</div></div>
                    @empty<div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>@endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section><x-slot name="heading">{{ __('uiux.dashboard.common.reports') }}</x-slot><div class="flex flex-wrap gap-3">@if($links['history'])<x-filament::button tag="a" :href="$links['history']" color="gray" icon="heroicon-o-clock">{{ __('finance.payment_history') }}</x-filament::button>@endif @if($links['revenue'])<x-filament::button tag="a" :href="$links['revenue']" color="gray" icon="heroicon-o-chart-bar">{{ __('finance.revenue_report') }}</x-filament::button>@endif</div></x-filament::section>
    </div>
</x-filament-panels::page>
