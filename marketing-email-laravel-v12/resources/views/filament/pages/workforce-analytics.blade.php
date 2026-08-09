<x-filament-panels::page>
    @php
        $a = $analytics;
        $money = static fn ($v) => number_format((float) $v, 0, ',', '.').' ₫';
    @endphp
    <div class="dth-analytics-shell" wire:loading.class="opacity-70">
        <div class="dth-analytics-toolbar"><div><div class="dth-analytics-toolbar__title">{{ __('analytics.workforce_analysis') }}</div><div class="dth-analytics-toolbar__subtitle">{{ __('analytics.workforce_analysis_subtitle') }}</div></div><div class="flex flex-wrap items-center gap-2"><x-analytics.period-filter /><x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button></div></div>

        <div class="dth-analytics-kpi-grid">
            <x-analytics.kpi-card :label="__('analytics.active_staff')" :value="number_format($a['summary']['active_staff'])" icon="heroicon-o-users" tone="info" />
            <x-analytics.kpi-card :label="__('analytics.departments')" :value="number_format($a['summary']['departments'])" icon="heroicon-o-building-office-2" tone="primary" />
            <x-analytics.kpi-card :label="__('analytics.recorded_activities')" :value="number_format($a['summary']['activities'])" icon="heroicon-o-bolt" tone="warning" />
            <x-analytics.kpi-card :label="__('analytics.revenue_impact')" :value="$money($a['summary']['revenue_impact'])" icon="heroicon-o-banknotes" tone="success" />
        </div>

        <div class="dth-analytics-layout-equal">
            <x-filament::section><x-slot name="heading">{{ __('analytics.activity_by_department') }}</x-slot><x-analytics.bar-chart :items="collect($a['departments'])->map(fn($r)=>['label'=>$r['name'],'value'=>$r['activity']])->all()" :money="false" tone="info" /></x-filament::section>
            <x-filament::section><x-slot name="heading">{{ __('analytics.business_impact_by_department') }}</x-slot><x-analytics.bar-chart :items="collect($a['departments'])->map(fn($r)=>['label'=>$r['name'],'value'=>$r['impact_value']])->all()" tone="finance" /></x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('analytics.staff_productivity_detail') }}</x-slot>
            <x-slot name="description">{{ __('analytics.activity_index_disclaimer') }}</x-slot>
            <div class="overflow-x-auto"><table class="dth-analytics-table"><thead><tr><th>{{ __('field.staff') }}</th><th>{{ __('field.department') }}</th><th>{{ __('analytics.activity_index') }}</th><th>{{ __('analytics.primary_output') }}</th><th>{{ __('analytics.secondary_output') }}</th><th>{{ __('analytics.business_impact') }}</th><th>{{ __('analytics.highlight') }}</th></tr></thead><tbody>@forelse($a['staff'] as $row)<tr><td><div class="font-semibold">{{ $row['name'] }}</div><div class="text-xs text-gray-500">{{ $row['employee_code'] }} · {{ $row['position'] }}</div></td><td>{{ $row['department'] }}</td><td><div class="dth-analytics-index"><div class="dth-analytics-index__track"><span style="width:{{ $row['activity_index'] }}%"></span></div><strong>{{ number_format($row['activity_index'],0) }}</strong></div></td><td><strong>{{ is_numeric($row['metric_1_value']) ? number_format((float)$row['metric_1_value'],0,',','.') : $row['metric_1_value'] }}</strong><div class="text-xs text-gray-500">{{ $row['metric_1_label'] }}</div></td><td><strong>{{ is_numeric($row['metric_2_value']) ? number_format((float)$row['metric_2_value'],0,',','.') : $row['metric_2_value'] }}</strong><div class="text-xs text-gray-500">{{ $row['metric_2_label'] }}</div></td><td>@if(in_array($row['department_key'],['sales','marketing','finance'],true))<strong>{{ $money($row['impact_value']) }}</strong>@else<strong>{{ number_format($row['customers']) }}</strong><div class="text-xs text-gray-500">{{ __('analytics.customers') }}</div>@endif</td><td class="max-w-md">{{ $row['highlight'] }}</td></tr>@empty<tr><td colspan="7" class="text-center text-gray-500">{{ __('uiux.dashboard.common.no_data') }}</td></tr>@endforelse</tbody></table></div>
        </x-filament::section>
        <div class="dth-note"><strong>{{ __('analytics.kpi_note_title') }}:</strong> {{ __('analytics.kpi_note') }}</div>
    </div>
</x-filament-panels::page>
