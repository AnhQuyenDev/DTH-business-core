<x-filament-panels::page>
    <div class="space-y-6">
        <div class="dth-dashboard-hero dth-tone-customer-service">
            <div>
                <p class="dth-eyebrow">{{ $isManager ? __('uiux.dashboard.common.manager_scope') : __('uiux.dashboard.common.personal_scope') }}</p>
                <h2>{{ $isManager ? __('uiux.dashboard.customer_service.title') : __('uiux.dashboard.customer_service.my_work') }}</h2>
                <p>{{ __('uiux.dashboard.customer_service.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($isManager)<x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button>@endif
                @if($leadUrl)
                    <x-filament::button tag="a" :href="$leadUrl" color="info" icon="heroicon-o-user-group">{{ __('resource.lead.plural') }}</x-filament::button>
                @endif
            </div>
        </div>

        <div class="dth-report-grid dth-report-grid-4">
            @foreach([
                [__('uiux.dashboard.customer_service.new_leads'), $summary['new'], 'heroicon-o-inbox', 'gray'],
                [__('uiux.dashboard.customer_service.unassigned'), $summary['unassigned'], 'heroicon-o-user-minus', 'warning'],
                [__('uiux.dashboard.customer_service.in_progress'), $summary['in_progress'], 'heroicon-o-phone', 'info'],
                [__('uiux.dashboard.customer_service.follow_up_today'), $summary['follow_up_today'], 'heroicon-o-calendar-days', 'primary'],
                [__('uiux.dashboard.customer_service.overdue'), $summary['overdue'], 'heroicon-o-exclamation-triangle', 'danger'],
                [__('uiux.dashboard.customer_service.qualified'), $summary['qualified'], 'heroicon-o-check-badge', 'success'],
            ] as [$label,$value,$icon,$tone])
                <div class="dth-metric-card" data-tone="{{ $tone }}">
                    <div class="flex items-center justify-between gap-3"><span class="dth-metric-label">{{ $label }}</span><x-filament::icon :icon="$icon" class="h-5 w-5" /></div>
                    <div class="dth-metric-value">{{ number_format($value) }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">{{ __('uiux.dashboard.customer_service.lead_status') }}</x-slot>
                <div class="space-y-4">
                    @php($max = max(1, collect($statusRows)->max('value') ?: 1))
                    @forelse($statusRows as $row)
                        <div class="dth-bar-row">
                            <div class="dth-bar-meta"><span>{{ $row['label'] }}</span><strong>{{ number_format($row['value']) }}</strong></div>
                            <div class="dth-bar-track"><span class="dth-bar-fill dth-bar-info" style="width: {{ max(3, ($row['value'] / $max) * 100) }}%"></span></div>
                        </div>
                    @empty
                        <div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('uiux.dashboard.customer_service.priority_queue') }}</x-slot>
                <div class="space-y-3">
                    @forelse($priority as $row)
                        <div class="dth-list-row">
                            <div><div class="font-semibold text-gray-950 dark:text-white">{{ $row['name'] }}</div><div class="text-xs text-gray-500">{{ $row['status'] }} · {{ $row['follow_up_at']?->format('d/m/Y H:i') }}</div></div>
                            @if($leadUrl)<a class="text-sm font-semibold text-primary-600 hover:underline" href="{{ \App\Filament\Resources\LeadResource::getUrl('view', ['record' => $row['id']]) }}">{{ __('action.view') }}</a>@endif
                        </div>
                    @empty
                        <div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        @if($isManager)
            <x-filament::section>
                <x-slot name="heading">{{ __('uiux.dashboard.customer_service.staff_workload') }}</x-slot>
                <div class="overflow-x-auto">
                    <table class="dth-report-table"><thead><tr><th class="text-left">{{ __('field.staff') }}</th><th class="text-left">{{ __('field.employee_code') }}</th><th class="text-right">{{ __('uiux.dashboard.common.count') }}</th></tr></thead><tbody>
                    @forelse($workload as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td>{{ $row['code'] ?: '—' }}</td><td class="text-right">{{ number_format($row['total']) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-gray-500">{{ __('uiux.dashboard.common.no_data') }}</td></tr>@endforelse
                    </tbody></table>
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <x-slot name="heading">{{ __('uiux.dashboard.common.schedule') }}</x-slot>
                <x-filament-widgets::widgets :columns="1" :data="$this->getWidgetData()" :widgets="[\App\Filament\Widgets\StaffScheduleWidget::class]" />
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
