<x-filament-panels::page class="fi-dashboard-page">
    <div x-data="{ tab: 'email' }" class="space-y-6">
        <div class="flex justify-center">
            <div class="inline-flex max-w-full gap-1 overflow-x-auto rounded-xl border border-gray-200 bg-white p-1 dark:border-white/10 dark:bg-gray-900">
                <template x-for="(label, key) in {{ json_encode($this->getTabs()) }}" :key="key">
                    <button
                        type="button"
                        x-on:click="tab = key"
                        x-bind:class="tab === key
                            ? 'bg-gray-100 text-gray-950 dark:bg-white/10 dark:text-white'
                            : 'text-gray-500 hover:text-gray-950 dark:text-gray-400 dark:hover:text-white'"
                        class="whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition"
                        x-text="label"
                    ></button>
                </template>
            </div>
        </div>

        <div>
            <div x-cloak x-show="tab === 'email'" x-transition.opacity.duration.150ms>
                <x-filament-widgets::widgets
                    :columns="$this->getColumns()"
                    :data="$this->getWidgetData()"
                    :widgets="[\App\Filament\Widgets\DashboardCampaignWidget::class]"
                />
            </div>

            <div x-cloak x-show="tab === 'marketing'" x-transition.opacity.duration.150ms>
                <x-filament-widgets::widgets
                    :columns="$this->getColumns()"
                    :data="$this->getWidgetData()"
                    :widgets="[
                        \App\Filament\Widgets\DashboardLandingPageWidget::class,
                        \App\Filament\Widgets\MarketingUtmReportWidget::class,
                    ]"
                />
            </div>

            <div x-cloak x-show="tab === 'customers'" x-transition.opacity.duration.150ms>
                <x-filament-widgets::widgets
                    :columns="$this->getColumns()"
                    :data="$this->getWidgetData()"
                    :widgets="[
                        \App\Filament\Widgets\DashboardCustomerStatsWidget::class,
                        \App\Filament\Widgets\DashboardSystemAlertsWidget::class,
                    ]"
                />
            </div>

            <div x-cloak x-show="tab === 'staff'" x-transition.opacity.duration.150ms>
                <x-filament-widgets::widgets
                    :columns="$this->getColumns()"
                    :data="$this->getWidgetData()"
                    :widgets="[
                        \App\Filament\Widgets\DashboardStaffWorkloadWidget::class,
                        \App\Filament\Widgets\DashboardStaffDetailTableWidget::class,
                        \App\Filament\Widgets\DashboardUpcomingScheduleWidget::class,
                    ]"
                />
            </div>
        </div>
    </div>
</x-filament-panels::page>
