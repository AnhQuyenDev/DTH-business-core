<x-filament-panels::page>
    <div class="dth-analytics-shell" wire:loading.class="opacity-70">
        <div class="dth-dashboard-hero dth-tone-customer-service"><div><p class="dth-eyebrow">{{ $isManager ? __('uiux.dashboard.common.manager_scope') : __('uiux.dashboard.common.personal_scope') }}</p><h2>{{ $isManager ? __('uiux.dashboard.customer_service.title') : __('uiux.dashboard.customer_service.my_work') }}</h2><p>{{ __('analytics.customer_service_dashboard_subtitle') }}</p></div><div class="flex flex-wrap items-center gap-2"><x-analytics.period-filter />@if($isManager)<x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button>@endif</div></div>

        <div class="dth-analytics-kpi-grid">
            <x-analytics.kpi-card :label="__('analytics.new')" :value="number_format($summary['new'])" icon="heroicon-o-user-plus" tone="info" />
            <x-analytics.kpi-card :label="__('analytics.leads_handled')" :value="number_format($summary['handled'])" icon="heroicon-o-phone-arrow-up-right" tone="primary" />
            <x-analytics.kpi-card :label="__('analytics.qualified_leads')" :value="number_format($summary['qualified'])" icon="heroicon-o-check-badge" tone="success" />
            <x-analytics.kpi-card :label="__('analytics.qualification_rate')" :value="number_format($summary['qualification_rate'],1).'%'" icon="heroicon-o-arrow-trending-up" tone="success" />
            <x-analytics.kpi-card :label="__('analytics.follow_up_today')" :value="number_format($summary['follow_up_today'])" icon="heroicon-o-calendar-days" tone="warning" />
            <x-analytics.kpi-card :label="__('analytics.overdue')" :value="number_format($summary['overdue'])" icon="heroicon-o-exclamation-triangle" tone="danger" />
            @if($isManager)<x-analytics.kpi-card :label="__('analytics.unassigned')" :value="number_format($summary['unassigned'])" icon="heroicon-o-user-minus" tone="warning" />@endif
        </div>

        <div class="dth-analytics-layout-2">
            <x-filament::section><x-slot name="heading">{{ __('analytics.lead_processing_status') }}</x-slot><x-analytics.bar-chart :items="$statusRows" :money="false" tone="info" /></x-filament::section>
            <x-filament::section><x-slot name="heading">{{ __('analytics.priority_followups') }}</x-slot><div class="space-y-2">@forelse($priority as $row)<div class="dth-list-row"><div><div class="font-semibold">{{ $row['name'] }}</div><div class="text-xs text-gray-500">{{ $row['status'] }}</div></div><div class="text-right"><div class="text-sm font-semibold {{ $row['follow_up_at']?->isPast() ? 'text-danger-600' : '' }}">{{ $row['follow_up_at']?->format('d/m/Y H:i') ?: '—' }}</div>@if($leadUrl)<a href="{{ \App\Filament\Resources\LeadResource::getUrl('view',['record'=>$row['id']]) }}" class="text-xs font-semibold text-primary-600 hover:underline">{{ __('action.view') }}</a>@endif</div></div>@empty<div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>@endforelse</div></x-filament::section>
        </div>

        @if($isManager && $workforce)
            <x-filament::section><x-slot name="heading">{{ __('analytics.team_productivity') }}</x-slot><x-slot name="description">{{ __('analytics.customer_service_team_description') }}</x-slot><div class="overflow-x-auto"><table class="dth-analytics-table"><thead><tr><th>{{ __('field.staff') }}</th><th>{{ __('analytics.activity_index') }}</th><th class="numeric">{{ __('analytics.leads_handled') }}</th><th class="numeric">{{ __('analytics.qualified_leads') }}</th><th class="numeric">{{ __('analytics.customer_interactions') }}</th><th>{{ __('analytics.highlight') }}</th></tr></thead><tbody>@forelse($workforce['staff'] as $row)<tr><td><div class="font-semibold">{{ $row['name'] }}</div><div class="text-xs text-gray-500">{{ $row['employee_code'] }}</div></td><td><div class="dth-analytics-index"><div class="dth-analytics-index__track"><span style="width:{{ $row['activity_index'] }}%"></span></div><strong>{{ number_format($row['activity_index'],0) }}</strong></div></td><td class="numeric">{{ $row['metric_1_value'] }}</td><td class="numeric">{{ $row['metric_2_value'] }}</td><td class="numeric">{{ $row['metric_3_value'] }}</td><td>{{ $row['highlight'] }}</td></tr>@empty<tr><td colspan="6" class="text-center text-gray-500">{{ __('uiux.dashboard.common.no_data') }}</td></tr>@endforelse</tbody></table></div>@if($workforceUrl)<div class="mt-4"><x-filament::button tag="a" :href="$workforceUrl" color="gray" icon="heroicon-o-users">{{ __('analytics.open_workforce_analysis') }}</x-filament::button></div>@endif</x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
