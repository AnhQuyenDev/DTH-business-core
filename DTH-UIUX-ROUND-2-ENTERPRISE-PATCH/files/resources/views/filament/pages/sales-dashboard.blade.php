<x-filament-panels::page>
    <div class="space-y-6">
        <div class="dth-dashboard-hero dth-tone-sales">
            <div>
                <p class="dth-eyebrow">{{ $isManager ? __('uiux.dashboard.common.manager_scope') : __('uiux.dashboard.common.personal_scope') }}</p>
                <h2>{{ $isManager ? __('uiux.dashboard.sales.manager_title') : __('uiux.dashboard.sales.staff_title') }}</h2>
                <p>{{ __('uiux.dashboard.sales.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($isManager)<x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button>@endif
                @if($links['opportunities'])<x-filament::button tag="a" :href="$links['opportunities']" color="warning" icon="heroicon-o-briefcase">{{ __('resource.opportunity.plural') }}</x-filament::button>@endif
            </div>
        </div>

        <div class="dth-report-grid dth-report-grid-4">
            @foreach([
                [__('uiux.dashboard.sales.open_pipeline'), $summary['open_pipeline'], 'heroicon-o-rectangle-stack', 'info', false],
                [__('uiux.dashboard.sales.pipeline_value'), $summary['pipeline_value'], 'heroicon-o-currency-dollar', 'warning', true],
                [__('uiux.dashboard.sales.won_month'), $summary['won_month'], 'heroicon-o-trophy', 'success', false],
                [__('uiux.dashboard.sales.cash_collected'), $summary['cash_collected'], 'heroicon-o-banknotes', 'success', true],
                [__('uiux.dashboard.sales.pending_approval'), $summary['pending_approval'], 'heroicon-o-shield-check', 'warning', false],
                [__('uiux.dashboard.sales.accepted_unpaid'), $summary['accepted_unpaid'], 'heroicon-o-clock', 'warning', false],
                [__('uiux.dashboard.sales.expiring'), $summary['expiring_soon'], 'heroicon-o-exclamation-triangle', 'danger', false],
            ] as [$label,$value,$icon,$tone,$money])
                <div class="dth-metric-card" data-tone="{{ $tone }}"><div class="flex items-center justify-between gap-3"><span class="dth-metric-label">{{ $label }}</span><x-filament::icon :icon="$icon" class="h-5 w-5" /></div><div class="dth-metric-value">{{ $money ? number_format($value).' VND' : number_format($value) }}</div></div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">{{ __('uiux.dashboard.sales.pipeline_chart') }}</x-slot>
                <div class="space-y-4">
                    @php($max = max(1, collect($pipelineRows)->max('value') ?: collect($pipelineRows)->max('count') ?: 1))
                    @forelse($pipelineRows as $row)
                        @php($metric = $row['value'] > 0 ? $row['value'] : $row['count'])
                        <div class="dth-bar-row"><div class="dth-bar-meta"><span>{{ $row['label'] }}</span><strong>{{ $row['value'] > 0 ? number_format($row['value']).' VND' : number_format($row['count']) }}</strong></div><div class="dth-bar-track"><span class="dth-bar-fill dth-bar-sales" style="width: {{ max(3, ($metric / $max) * 100) }}%"></span></div></div>
                    @empty<div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>@endforelse
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('uiux.dashboard.sales.my_recent_opportunities') }}</x-slot>
                <div class="space-y-3">
                    @forelse($recent as $row)
                        <div class="dth-list-row"><div><div class="font-semibold text-gray-950 dark:text-white">{{ $row['code'] }} · {{ $row['title'] }}</div><div class="text-xs text-gray-500">{{ $row['party'] }} · {{ $row['stage'] }} @if($row['expected_close_date']) · {{ $row['expected_close_date']->format('d/m/Y') }} @endif</div></div><div class="text-right"><div class="font-semibold">{{ number_format($row['value']) }} VND</div>@if($links['opportunities'])<a href="{{ \App\Filament\Resources\Sales\OpportunityResource::getUrl('view', ['record' => $row['id']]) }}" class="text-xs font-semibold text-primary-600 hover:underline">{{ __('action.view') }}</a>@endif</div></div>
                    @empty<div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>@endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section><x-slot name="heading">{{ __('uiux.dashboard.common.reports') }}</x-slot><div class="flex flex-wrap gap-3">@if($links['quotations'])<x-filament::button tag="a" :href="$links['quotations']" color="gray" icon="heroicon-o-document-text">{{ __('resource.quotation.plural') }}</x-filament::button>@endif @if($links['approvals'])<x-filament::button tag="a" :href="$links['approvals']" color="gray" icon="heroicon-o-shield-check">{{ __('navigation.quotation_approvals') }}</x-filament::button>@endif @if($links['revenue'])<x-filament::button tag="a" :href="$links['revenue']" color="gray" icon="heroicon-o-chart-bar">{{ __('finance.revenue_report') }}</x-filament::button>@endif</div></x-filament::section>
    </div>
</x-filament-panels::page>
