<x-filament-panels::page>
    <div class="space-y-6">
        <div class="dth-report-grid dth-report-grid-4">
            @foreach([
                [__('dashboard.sales.total_quotations'), number_format($summary['total']), __('dashboard.sales.total_value').': '.number_format($summary['total_value']).' VND', 'heroicon-o-document-text'],
                [__('dashboard.sales.sent'), number_format($summary['sent']), null, 'heroicon-o-paper-airplane'],
                [__('dashboard.sales.viewed'), number_format($summary['viewed']), null, 'heroicon-o-eye'],
                [__('dashboard.sales.accepted'), number_format($summary['accepted']), __('dashboard.sales.accepted_value').': '.number_format($summary['accepted_value']).' VND', 'heroicon-o-check-circle'],
            ] as [$label, $value, $description, $icon])
                <div class="dth-metric-card">
                    <div class="flex items-center justify-between gap-3">
                        <span class="dth-metric-label">{{ $label }}</span>
                        <x-filament::icon :icon="$icon" class="h-5 w-5 text-gray-400" />
                    </div>
                    <div class="dth-metric-value text-gray-950 dark:text-white">{{ $value }}</div>
                    @if($description)<div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $description }}</div>@endif
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-filament::section class="xl:col-span-2">
                <x-slot name="heading">{{ __('dashboard.sales.pipeline_health') }}</x-slot>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
                    @foreach([
                        [__('dashboard.sales.draft'), $summary['draft']],
                        [__('dashboard.sales.pending_approval'), $summary['pending_approval']],
                        [__('dashboard.sales.accepted_unpaid'), $summary['accepted_unpaid']],
                        [__('dashboard.sales.rejected'), $summary['rejected']],
                        [__('dashboard.sales.expired'), $summary['expired']],
                    ] as [$label, $value])
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</div>
                            <div class="mt-1 text-xl font-bold text-gray-950 dark:text-white">{{ number_format($value) }}</div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('dashboard.sales.conversion_heading') }}</x-slot>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between text-sm"><span class="text-gray-500 dark:text-gray-400">{{ __('dashboard.sales.sent_to_viewed_rate') }}</span><strong>{{ $conversion['sent_to_viewed'] }}%</strong></div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/5"><div class="h-full rounded-full bg-primary-500" style="width: {{ min(100, $conversion['sent_to_viewed']) }}%"></div></div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm"><span class="text-gray-500 dark:text-gray-400">{{ __('dashboard.sales.viewed_to_accepted_rate') }}</span><strong>{{ $conversion['viewed_to_accepted'] }}%</strong></div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/5"><div class="h-full rounded-full bg-success-500" style="width: {{ min(100, $conversion['viewed_to_accepted']) }}%"></div></div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        @if($summary['expiring_soon'] > 0)
            <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning">
                <div class="text-sm text-gray-700 dark:text-gray-300">@lang('dashboard.sales.expiring_soon_alert', ['count' => $summary['expiring_soon']])</div>
            </x-filament::section>
        @endif

        <x-filament::section>
            <x-slot name="heading">{{ __('dashboard.sales.staff_follow_up') }}</x-slot>
            @if(count($followUp) > 0)
                <div class="overflow-x-auto">
                    <table class="dth-report-table">
                        <thead><tr><th class="text-left">{{ __('field.staff') }}</th><th class="text-right">{{ __('dashboard.sales.pending_quotations') }}</th></tr></thead>
                        <tbody>
                            @foreach($followUp as $item)
                                <tr><td class="font-semibold">{{ $item['staff'] }}</td><td class="text-right">{{ $item['count'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.sales.no_follow_up') }}</div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
