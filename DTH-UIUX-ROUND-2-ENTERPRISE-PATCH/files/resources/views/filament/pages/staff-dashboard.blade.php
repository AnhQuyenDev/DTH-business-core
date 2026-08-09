<x-filament-panels::page>
    <div class="uiux-dashboard uiux-dashboard--staff space-y-6">
        <section class="uiux-dashboard-hero">
            <div>
                <p class="uiux-dashboard-eyebrow">{{ __('uiux.dashboard.common.personal_scope') }}</p>
                <h2>{{ __('uiux.dashboard.staff.title') }}</h2>
                <p>{{ __('uiux.dashboard.staff.subtitle') }}</p>
            </div>
        </section>

        @if($staff)
            <section class="uiux-kpi-grid uiux-kpi-grid--4">
                <article class="uiux-kpi-card">
                    <span>{{ __('field.employee_code') }}</span>
                    <strong>{{ $staff->employee_code ?: '—' }}</strong>
                </article>
                <article class="uiux-kpi-card">
                    <span>{{ __('field.department') }}</span>
                    <strong>{{ $staff->department?->name ?: '—' }}</strong>
                </article>
                <article class="uiux-kpi-card">
                    <span>{{ __('field.position') }}</span>
                    <strong>{{ $staff->position?->name ?: '—' }}</strong>
                </article>
                <article class="uiux-kpi-card">
                    <span>{{ __('field.employment_status') }}</span>
                    <strong>{{ $staff->employment_status?->label() ?: '—' }}</strong>
                </article>
            </section>
        @endif

        @if($showSchedule)
            <section class="uiux-report-section">
                <div class="uiux-section-heading">
                    <div>
                        <h3>{{ __('uiux.dashboard.common.schedule') }}</h3>
                        <p>{{ __('uiux.dashboard.staff.schedule_helper') }}</p>
                    </div>
                </div>
                <x-filament-widgets::widgets
                    :columns="1"
                    :data="$this->getWidgetData()"
                    :widgets="$this->getWidgets()"
                />
            </section>
        @else
            <section class="uiux-empty-state">
                <div class="uiux-empty-state__icon">✓</div>
                <h3>{{ __('uiux.dashboard.staff.ready') }}</h3>
                <p>{{ __('uiux.dashboard.staff.ready_helper') }}</p>
            </section>
        @endif
    </div>
</x-filament-panels::page>
