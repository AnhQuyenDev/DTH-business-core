<x-filament-panels::page>
    <div class="dth-analytics-shell" wire:loading.class="opacity-70">
        <div class="dth-dashboard-hero dth-tone-customer-service">
            <div>
                <p class="dth-eyebrow">{{ $isManager ? __('uiux.dashboard.common.manager_scope') : __('uiux.dashboard.common.personal_scope') }}</p>
                <h2>{{ $isManager ? __('uiux.dashboard.customer_service.title') : __('uiux.dashboard.customer_service.my_work') }}</h2>
                <p>{{ __('v1.analytics.customer_care_subtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-analytics.period-filter />
                @if($isManager)
                    <x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button>
                @endif
            </div>
        </div>

        <div class="dth-analytics-kpi-grid">
            <x-analytics.kpi-card :label="__('v1.analytics.active_customers')" :value="number_format($summary['active_customers'])" icon="heroicon-o-user-group" tone="success" />
            <x-analytics.kpi-card :label="__('v1.analytics.new_customers')" :value="number_format($summary['new_customers'])" icon="heroicon-o-user-plus" tone="info" />
            <x-analytics.kpi-card :label="__('analytics.customer_interactions')" :value="number_format($summary['customer_interactions'])" icon="heroicon-o-chat-bubble-left-right" tone="primary" />
            <x-analytics.kpi-card :label="__('v1.analytics.open_tickets')" :value="number_format($summary['open_tickets'])" icon="heroicon-o-lifebuoy" tone="warning" />
            <x-analytics.kpi-card :label="__('analytics.follow_up_today')" :value="number_format($summary['follow_up_today'])" icon="heroicon-o-calendar-days" tone="warning" />
            <x-analytics.kpi-card :label="__('analytics.overdue')" :value="number_format($summary['overdue'])" icon="heroicon-o-exclamation-triangle" tone="danger" />
            @if($isManager)
                <x-analytics.kpi-card :label="__('v1.analytics.unassigned_customers')" :value="number_format($summary['unassigned'])" icon="heroicon-o-user-minus" tone="warning" />
            @endif
        </div>

        <div class="dth-analytics-layout-2">
            <x-filament::section>
                <x-slot name="heading">{{ __('v1.analytics.ticket_status') }}</x-slot>
                <x-analytics.bar-chart :items="$statusRows" :money="false" tone="info" />
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">{{ __('v1.analytics.priority_support') }}</x-slot>
                <div class="space-y-2">
                    @forelse($priority as $row)
                        <div class="dth-list-row">
                            <div>
                                <div class="font-semibold">{{ $row['name'] }}</div>
                                <div class="text-xs text-gray-500">{{ $row['subject'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold">{{ $row['status'] }}</div>
                                <div class="text-xs text-gray-500">{{ $row['last_activity_at']?->format('d/m/Y H:i') ?: '—' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>
                    @endforelse
                </div>
                @if($ticketUrl)
                    <div class="mt-4"><x-filament::button tag="a" :href="$ticketUrl" color="gray" icon="heroicon-o-lifebuoy">{{ __('v1.support.resource_plural') }}</x-filament::button></div>
                @endif
            </x-filament::section>
        </div>

        @if($isManager && $workforce)
            <x-filament::section>
                <x-slot name="heading">{{ __('analytics.team_productivity') }}</x-slot>
                <x-slot name="description">{{ __('v1.analytics.customer_care_team_description') }}</x-slot>
                <div class="overflow-x-auto">
                    <table class="dth-analytics-table">
                        <thead><tr><th>{{ __('field.staff') }}</th><th>{{ __('analytics.activity_index') }}</th><th class="numeric">{{ __('analytics.customer_interactions') }}</th><th class="numeric">{{ __('v1.analytics.customers_cared') }}</th><th class="numeric">{{ __('v1.analytics.tickets_resolved') }}</th><th>{{ __('analytics.highlight') }}</th></tr></thead>
                        <tbody>
                        @forelse($workforce['staff'] as $row)
                            <tr>
                                <td><div class="font-semibold">{{ $row['name'] }}</div><div class="text-xs text-gray-500">{{ $row['employee_code'] }}</div></td>
                                <td><div class="dth-analytics-index"><div class="dth-analytics-index__track"><span style="width:{{ $row['activity_index'] }}%"></span></div><strong>{{ number_format($row['activity_index'],0) }}</strong></div></td>
                                <td class="numeric">{{ $row['metric_1_value'] }}</td>
                                <td class="numeric">{{ $row['metric_2_value'] }}</td>
                                <td class="numeric">{{ $row['metric_3_value'] }}</td>
                                <td>{{ $row['highlight'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-gray-500">{{ __('uiux.dashboard.common.no_data') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($workforceUrl)
                    <div class="mt-4"><x-filament::button tag="a" :href="$workforceUrl" color="gray" icon="heroicon-o-users">{{ __('analytics.open_workforce_analysis') }}</x-filament::button></div>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
