<x-filament-panels::page class="fi-dashboard-page">
    <div x-data="{ tab: 'email' }" class="space-y-6">
        <div class="inline-flex items-center gap-2.5 p-1.5 bg-gray-950 rounded-2xl border-0">
            <template x-for="(label, key) in {{ json_encode($this->getTabs()) }}" :key="key">
                <button
                    type="button"
                    x-on:click="tab = key"
                    x-bind:class="{
                        '!bg-amber-500 !text-white font-semibold shadow-md shadow-amber-500/20': tab == key,
                        'text-gray-300 hover:text-white hover:bg-gray-800/60 font-medium': tab != key,
                    }"
                    x-bind:style="tab == key ? 'background-color: #eab308 !important; color: #ffffff !important;' : ''"
                    class="px-4 py-2 rounded-xl text-sm tracking-wide transition-all duration-200 cursor-pointer whitespace-nowrap border-0 outline-none select-none"
                    x-text="label"
                >
                </button>
            </template>
        </div>
        
        <div class="mt-6">
            <div x-show="tab === 'email'" x-transition:enter.duration.200>
                <x-filament-widgets::widgets
                    :columns="$this->getColumns()"
                    :data="$this->getWidgetData()"
                    :widgets="[
                        \App\Filament\Widgets\DashboardCampaignWidget::class,
                    ]"
                />
            </div>

            <div x-show="tab === 'marketing'" x-transition:enter.duration.200>
                <x-filament-widgets::widgets
                    :columns="$this->getColumns()"
                    :data="$this->getWidgetData()"
                    :widgets="[
                        \App\Filament\Widgets\DashboardLandingPageWidget::class,
                        \App\Filament\Widgets\MarketingUtmReportWidget::class,
                    ]"
                />
            </div>

            <div x-show="tab === 'customers'" x-transition:enter.duration.200>
                <x-filament-widgets::widgets
                    :columns="$this->getColumns()"
                    :data="$this->getWidgetData()"
                    :widgets="[
                        \App\Filament\Widgets\DashboardCustomerStatsWidget::class,
                        \App\Filament\Widgets\DashboardSystemAlertsWidget::class,
                    ]"
                />
            </div>

            <div x-show="tab === 'staff'" x-transition:enter.duration.200>
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
