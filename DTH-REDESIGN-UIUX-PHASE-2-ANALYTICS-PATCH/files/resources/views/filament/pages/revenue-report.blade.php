<x-filament-panels::page>
    @php
        $r = $this->report;
        $s = $r['summary'];
        $money = static fn ($value): string => number_format((float) $value, 0, ',', '.').' ₫';
    @endphp

    <div class="dth-analytics-shell" wire:loading.class="opacity-70">
        <div class="dth-analytics-toolbar">
            <div><div class="dth-analytics-toolbar__title">{{ __('analytics.revenue_analysis') }}</div><div class="dth-analytics-toolbar__subtitle">{{ __('analytics.revenue_analysis_subtitle') }}</div></div>
            <div class="flex flex-wrap gap-2"><x-filament::button wire:click="resetFilters" color="gray" icon="heroicon-o-arrow-path">{{ __('action.reset') }}</x-filament::button><x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button></div>
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('finance.report.filters_heading') }}</x-slot>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div><label class="dth-filter-label">{{ __('finance.report.start_date') }}</label><x-filament::input.wrapper><x-filament::input type="date" wire:model.live="startDate" /></x-filament::input.wrapper></div>
                <div><label class="dth-filter-label">{{ __('finance.report.end_date') }}</label><x-filament::input.wrapper><x-filament::input type="date" wire:model.live="endDate" /></x-filament::input.wrapper></div>
                <div><label class="dth-filter-label">{{ __('finance.report.marketing_campaign') }}</label><x-filament::input.wrapper><x-filament::input.select wire:model.live="marketingCampaignId"><option value="">{{ __('finance.report.all') }}</option>@foreach($this->options['marketing_campaigns'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
                <div><label class="dth-filter-label">{{ __('finance.report.email_campaign') }}</label><x-filament::input.wrapper><x-filament::input.select wire:model.live="emailCampaignId"><option value="">{{ __('finance.report.all') }}</option>@foreach($this->options['email_campaigns'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
                <div><label class="dth-filter-label">{{ __('finance.report.landing_page') }}</label><x-filament::input.wrapper><x-filament::input.select wire:model.live="landingPageId"><option value="">{{ __('finance.report.all') }}</option>@foreach($this->options['landing_pages'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
                <div><label class="dth-filter-label">{{ __('finance.report.service') }}</label><x-filament::input.wrapper><x-filament::input.select wire:model.live="serviceId"><option value="">{{ __('finance.report.all') }}</option>@foreach($this->options['services'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
                <div><label class="dth-filter-label">{{ __('finance.report.package') }}</label><x-filament::input.wrapper><x-filament::input.select wire:model.live="servicePackageId"><option value="">{{ __('finance.report.all') }}</option>@foreach($this->options['packages'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
                <div><label class="dth-filter-label">{{ __('finance.report.sales_staff') }}</label><x-filament::input.wrapper><x-filament::input.select wire:model.live="salesStaffId"><option value="">{{ __('finance.report.all') }}</option>@foreach($this->options['sales_staff'] as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div>
                <div><label class="dth-filter-label">{{ __('finance.report.utm_source') }}</label><x-filament::input.wrapper><x-filament::input wire:model.live.debounce.400ms="utmSource" /></x-filament::input.wrapper></div>
                <div><label class="dth-filter-label">{{ __('finance.report.utm_campaign') }}</label><x-filament::input.wrapper><x-filament::input wire:model.live.debounce.400ms="utmCampaign" /></x-filament::input.wrapper></div>
            </div>
        </x-filament::section>

        <div class="dth-analytics-kpi-grid">
            <x-analytics.kpi-card :label="__('analytics.gross_collected')" :value="$money($s['gross_collected'])" icon="heroicon-o-banknotes" tone="success" :delta="$r['deltas']['gross_collected']" />
            <x-analytics.kpi-card :label="__('analytics.net_revenue')" :value="$money($s['net_revenue'])" icon="heroicon-o-chart-bar-square" tone="warning" :delta="$r['deltas']['net_revenue']" />
            <x-analytics.kpi-card :label="__('analytics.payments')" :value="number_format($s['payments'])" icon="heroicon-o-credit-card" tone="info" :delta="$r['deltas']['payments']" />
            <x-analytics.kpi-card :label="__('analytics.paid_customers')" :value="number_format($s['customers'])" icon="heroicon-o-user-group" tone="primary" :delta="$r['deltas']['customers']" />
            <x-analytics.kpi-card :label="__('analytics.tax')" :value="$money($s['tax'])" icon="heroicon-o-receipt-percent" tone="info" />
            <x-analytics.kpi-card :label="__('analytics.average_payment')" :value="$money($s['average_payment'])" icon="heroicon-o-scale" tone="primary" />
            <x-analytics.kpi-card :label="__('analytics.pending_verification')" :value="$money($s['pending_verification'])" icon="heroicon-o-clock" tone="warning" />
            <x-analytics.kpi-card :label="__('analytics.outstanding')" :value="$money($s['outstanding_accepted'])" icon="heroicon-o-exclamation-triangle" tone="danger" />
        </div>

        <div class="dth-analytics-layout-2">
            <x-filament::section><x-slot name="heading">{{ __('analytics.revenue_comparison') }}</x-slot><x-slot name="description">{{ __('analytics.revenue_comparison_description') }}</x-slot><x-analytics.line-chart :current="$r['trend']['current']" :previous="$r['trend']['previous']" /></x-filament::section>
            <x-filament::section><x-slot name="heading">{{ __('analytics.revenue_by_source') }}</x-slot><x-analytics.donut-chart :items="collect($r['sources'])->map(fn($x)=>['label'=>$x['name'],'value'=>$x['net_revenue']])->all()" :center-label="__('analytics.net_revenue')" /></x-filament::section>
        </div>

        <div class="dth-analytics-layout-equal">
            <x-filament::section><x-slot name="heading">{{ __('analytics.revenue_by_service') }}</x-slot><x-analytics.bar-chart :items="collect($r['services'])->map(fn($x)=>['label'=>$x['name'],'value'=>$x['net_revenue']])->all()" tone="sales" /></x-filament::section>
            <x-filament::section><x-slot name="heading">{{ __('analytics.revenue_by_sales') }}</x-slot><x-analytics.bar-chart :items="collect($r['sales'])->map(fn($x)=>['label'=>$x['name'],'value'=>$x['net_revenue']])->all()" tone="finance" /></x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('analytics.marketing_campaign_comparison') }}</x-slot>
            <div class="overflow-x-auto"><table class="dth-analytics-table"><thead><tr><th>{{ __('field.name') }}</th><th class="numeric">{{ __('analytics.budget') }}</th><th class="numeric">{{ __('analytics.leads') }}</th><th class="numeric">{{ __('analytics.paid_customers') }}</th><th class="numeric">{{ __('analytics.conversion_rate') }}</th><th class="numeric">{{ __('analytics.net_revenue') }}</th><th class="numeric">ROAS</th></tr></thead><tbody>@forelse($r['marketing_campaigns'] as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td class="numeric">{{ $money($row['budget']) }}</td><td class="numeric">{{ $row['leads'] }}</td><td class="numeric">{{ $row['paid_customers'] }}</td><td class="numeric">{{ number_format($row['conversion_rate'],1) }}%</td><td class="numeric font-semibold">{{ $money($row['net_revenue']) }}</td><td class="numeric">{{ $row['roas']===null?'—':number_format($row['roas'],2).'x' }}</td></tr>@empty<tr><td colspan="7" class="text-center text-gray-500">{{ __('uiux.dashboard.common.no_data') }}</td></tr>@endforelse</tbody></table></div>
        </x-filament::section>

        <div class="dth-analytics-layout-equal">
            <x-filament::section><x-slot name="heading">{{ __('analytics.top_landing_pages') }}</x-slot><div class="overflow-x-auto"><table class="dth-analytics-table"><thead><tr><th>{{ __('resource.landing_page.singular') }}</th><th class="numeric">{{ __('analytics.leads') }}</th><th class="numeric">{{ __('analytics.paid_customers') }}</th><th class="numeric">{{ __('analytics.conversion_rate') }}</th><th class="numeric">{{ __('analytics.net_revenue') }}</th></tr></thead><tbody>@forelse(array_slice($r['landing_pages'],0,8) as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td class="numeric">{{ $row['leads'] }}</td><td class="numeric">{{ $row['paid_customers'] }}</td><td class="numeric">{{ number_format($row['conversion_rate'],1) }}%</td><td class="numeric">{{ $money($row['net_revenue']) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-gray-500">{{ __('uiux.dashboard.common.no_data') }}</td></tr>@endforelse</tbody></table></div></x-filament::section>
            <x-filament::section><x-slot name="heading">{{ __('analytics.top_customers') }}</x-slot><div class="overflow-x-auto"><table class="dth-analytics-table"><thead><tr><th>{{ __('resource.customer.singular') }}</th><th class="numeric">{{ __('analytics.payments') }}</th><th class="numeric">{{ __('analytics.net_revenue') }}</th></tr></thead><tbody>@forelse(array_slice($r['customers'],0,8) as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td class="numeric">{{ $row['payments'] }}</td><td class="numeric">{{ $money($row['net_revenue']) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-gray-500">{{ __('uiux.dashboard.common.no_data') }}</td></tr>@endforelse</tbody></table></div></x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
