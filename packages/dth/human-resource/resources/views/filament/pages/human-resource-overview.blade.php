@php
    use Dth\HumanResource\Enums\BusinessFunction;
    use Dth\HumanResource\Enums\EmploymentStatus;
    use Dth\HumanResource\Filament\Resources\DepartmentResource;
    use Dth\HumanResource\Filament\Resources\EmployeeResource;
    use Dth\HumanResource\Support\UiText;

    $m = $snapshot['metrics'] ?? [];
    $number = static fn ($value) => number_format((int) $value, 0, ',', '.');
    $maxDepartment = max(1, collect($snapshot['departments'] ?? [])->max('count') ?? 1);
@endphp

<x-filament-panels::page>
    <div class="dth-hr-dashboard-shell">
        <div class="dth-hr-kpi-grid">
            <a href="{{ EmployeeResource::getUrl('index') }}" class="dth-hr-kpi-card" data-tone="blue">
                <div class="dth-hr-kpi-icon"><x-filament::icon icon="heroicon-o-users" /></div>
                <div class="dth-hr-kpi-body">
                    <div class="dth-hr-kpi-label">{{ UiText::get('dashboard.metrics.employees', 'Employees') }}</div>
                    <div class="dth-hr-kpi-value">{{ $number($m['employees'] ?? 0) }}</div>
                    <div class="dth-hr-kpi-help">{{ UiText::get('dashboard.help.active_employees', ':count currently active', ['count' => $number($m['active_employees'] ?? 0)]) }}</div>
                </div>
                <span class="dth-hr-kpi-arrow"><x-filament::icon icon="heroicon-o-arrow-up-right" /></span>
                <span class="dth-hr-kpi-glow"></span>
            </a>

            <a href="{{ DepartmentResource::getUrl('index') }}" class="dth-hr-kpi-card" data-tone="violet">
                <div class="dth-hr-kpi-icon"><x-filament::icon icon="heroicon-o-building-office-2" /></div>
                <div class="dth-hr-kpi-body">
                    <div class="dth-hr-kpi-label">{{ UiText::get('dashboard.metrics.departments', 'Departments') }}</div>
                    <div class="dth-hr-kpi-value">{{ $number($m['departments'] ?? 0) }}</div>
                    <div class="dth-hr-kpi-help">{{ UiText::get('dashboard.help.organization', 'Organization structure managed by HR') }}</div>
                </div>
                <span class="dth-hr-kpi-arrow"><x-filament::icon icon="heroicon-o-arrow-up-right" /></span>
                <span class="dth-hr-kpi-glow"></span>
            </a>

            <div class="dth-hr-kpi-card" data-tone="amber">
                <div class="dth-hr-kpi-icon"><x-filament::icon icon="heroicon-o-briefcase" /></div>
                <div class="dth-hr-kpi-body">
                    <div class="dth-hr-kpi-label">{{ UiText::get('dashboard.metrics.positions', 'Job titles') }}</div>
                    <div class="dth-hr-kpi-value">{{ $number($m['positions'] ?? 0) }}</div>
                    <div class="dth-hr-kpi-help">{{ UiText::get('dashboard.help.positions', 'Reusable job-title master data') }}</div>
                </div>
                <span class="dth-hr-kpi-glow"></span>
            </div>

            <div class="dth-hr-kpi-card" data-tone="green">
                <div class="dth-hr-kpi-icon"><x-filament::icon icon="heroicon-o-calendar-days" /></div>
                <div class="dth-hr-kpi-body">
                    <div class="dth-hr-kpi-label">{{ UiText::get('dashboard.metrics.on_leave', 'Unavailable today') }}</div>
                    <div class="dth-hr-kpi-value">{{ $number($m['on_leave'] ?? 0) }}</div>
                    <div class="dth-hr-kpi-help">{{ UiText::get('dashboard.help.on_leave', 'Leave, sick leave or absence') }}</div>
                </div>
                <span class="dth-hr-kpi-glow"></span>
            </div>
        </div>

        <div class="dth-hr-dashboard-grid dth-hr-dashboard-grid--2">
            <section class="dth-hr-dashboard-card">
                <div class="dth-hr-card-head">
                    <div>
                        <h3>{{ UiText::get('dashboard.sections.employment_status', 'Employment status') }}</h3>
                        <p>{{ UiText::get('dashboard.help.employment_status', 'Current workforce status across all employee records.') }}</p>
                    </div>
                    <span class="dth-hr-card-icon" data-tone="blue"><x-filament::icon icon="heroicon-o-identification" /></span>
                </div>

                <div class="dth-hr-status-list">
                    @foreach(EmploymentStatus::cases() as $status)
                        @php $count = (int) ($snapshot['employment_statuses'][$status->value] ?? 0); @endphp
                        <div class="dth-hr-status-row">
                            <span class="dth-hr-badge dth-hr-badge--{{ $status->color() }}">{{ $status->label() }}</span>
                            <strong>{{ $number($count) }}</strong>
                        </div>
                    @endforeach

                    <div class="dth-hr-status-row dth-hr-status-row--summary">
                        <span class="dth-hr-summary-label">
                            <x-filament::icon icon="heroicon-o-key" />
                            {{ UiText::get('dashboard.metrics.linked_users', 'Linked login accounts') }}
                        </span>
                        <strong>{{ $number($m['linked_users'] ?? 0) }}</strong>
                    </div>
                </div>
            </section>

            <section class="dth-hr-dashboard-card">
                <div class="dth-hr-card-head">
                    <div>
                        <h3>{{ UiText::get('dashboard.sections.departments', 'Active employees by department') }}</h3>
                        <p>{{ UiText::get('dashboard.help.departments', 'Workforce distribution across the current organization structure.') }}</p>
                    </div>
                    <span class="dth-hr-card-icon" data-tone="violet"><x-filament::icon icon="heroicon-o-building-office" /></span>
                </div>

                <div class="dth-hr-department-list">
                    @forelse($snapshot['departments'] ?? [] as $department)
                        @php
                            $width = max(3, round(((int) $department['count'] / $maxDepartment) * 100));
                            $function = $department['function'] ? BusinessFunction::tryFrom($department['function']) : null;
                        @endphp
                        <div class="dth-hr-department-item">
                            <div class="dth-hr-department-head">
                                <div>
                                    <a class="dth-hr-department-name" href="{{ DepartmentResource::getUrl('edit', ['record' => $department['id']]) }}">{{ $department['name'] }}</a>
                                    @if($function)
                                        <div class="dth-hr-muted">{{ $function->label() }}</div>
                                    @endif
                                </div>
                                <div class="dth-hr-department-count">
                                    <strong>{{ $number($department['count']) }}</strong>
                                    <span>{{ UiText::get('models.employee_plural_short', 'employees') }}</span>
                                </div>
                            </div>
                            <div class="dth-hr-progress"><span style="width:{{ $width }}%"></span></div>
                        </div>
                    @empty
                        <div class="dth-hr-empty">{{ UiText::get('dashboard.empty.no_departments', 'No departments yet.') }}</div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="dth-hr-dashboard-card">
            <div class="dth-hr-card-head">
                <div>
                    <h3>{{ UiText::get('dashboard.sections.recent_employees', 'Recently added employees') }}</h3>
                    <p>{{ UiText::get('dashboard.help.recent_employees', 'Latest employee records added to the Human Resource master data.') }}</p>
                </div>
                <span class="dth-hr-card-icon" data-tone="green"><x-filament::icon icon="heroicon-o-clock" /></span>
            </div>

            <div class="dth-hr-table-wrap">
                <table class="dth-hr-data-table">
                    <thead>
                        <tr>
                            <th>{{ UiText::get('fields.employee_code', 'Employee code') }}</th>
                            <th>{{ UiText::get('fields.full_name', 'Full name') }}</th>
                            <th>{{ UiText::get('fields.department', 'Department') }}</th>
                            <th>{{ UiText::get('fields.position', 'Job title') }}</th>
                            <th>{{ UiText::get('common.fields.status', 'Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($snapshot['recent_employees'] ?? [] as $employee)
                            @php $status = EmploymentStatus::tryFrom((string) $employee['status']); @endphp
                            <tr>
                                <td>
                                    <a class="dth-hr-record-link" href="{{ EmployeeResource::getUrl('view', ['record' => $employee['id']]) }}">{{ $employee['code'] }}</a>
                                    <small>{{ optional($employee['created_at'])->format('d/m/Y') }}</small>
                                </td>
                                <td><strong>{{ $employee['name'] }}</strong></td>
                                <td>{{ $employee['department'] ?: '—' }}</td>
                                <td>{{ $employee['position'] ?: '—' }}</td>
                                <td><span class="dth-hr-badge dth-hr-badge--{{ $status?->color() ?? 'gray' }}">{{ $status?->label() ?? $employee['status'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="dth-hr-empty">{{ UiText::get('dashboard.empty.no_employees', 'No employees yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dth-hr-dashboard-card">
            <div class="dth-hr-card-head">
                <div>
                    <h3>{{ UiText::get('dashboard.sections.boundary', 'Module ownership rule') }}</h3>
                    <p>{{ UiText::get('dashboard.help.boundary', 'Human Resource remains the single source of employee identity used by other modules.') }}</p>
                </div>
                <span class="dth-hr-card-icon" data-tone="amber"><x-filament::icon icon="heroicon-o-squares-2x2" /></span>
            </div>

            <div class="dth-hr-principle-grid">
                <div class="dth-hr-principle-card" data-tone="blue">
                    <span class="dth-hr-principle-icon"><x-filament::icon icon="heroicon-o-identification" /></span>
                    <div>
                        <div class="dth-hr-principle-title">{{ UiText::get('dashboard.boundary.hr_title', 'Human Resource owns identity') }}</div>
                        <div class="dth-hr-muted">{{ UiText::get('dashboard.boundary.hr_body', 'Employee profile, department, job title, employment status and availability are maintained here once.') }}</div>
                    </div>
                </div>
                <div class="dth-hr-principle-card" data-tone="violet">
                    <span class="dth-hr-principle-icon"><x-filament::icon icon="heroicon-o-user-group" /></span>
                    <div>
                        <div class="dth-hr-principle-title">{{ UiText::get('dashboard.boundary.crm_title', 'CRM owns CRM capacity') }}</div>
                        <div class="dth-hr-muted">{{ UiText::get('dashboard.boundary.crm_body', 'CRM only attaches Lead/Customer capacity, distribution weight and assignment eligibility to an HR employee.') }}</div>
                    </div>
                </div>
                <div class="dth-hr-principle-card" data-tone="green">
                    <span class="dth-hr-principle-icon"><x-filament::icon icon="heroicon-o-link" /></span>
                    <div>
                        <div class="dth-hr-principle-title">{{ UiText::get('dashboard.boundary.no_duplicate_title', 'No duplicated employee master') }}</div>
                        <div class="dth-hr-muted">{{ UiText::get('dashboard.boundary.no_duplicate_body', 'Email, Marketing and future modules reuse the HR employee instead of creating separate staff records.') }}</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
<style>
    .dth-hr-dashboard-shell { display: flex; flex-direction: column; gap: 14px; }
    .dth-hr-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; }
    .dth-hr-kpi-card {
        --tone: 37,99,235;
        position: relative;
        min-height: 112px;
        overflow: hidden;
        display: grid;
        grid-template-columns: 44px minmax(0,1fr) auto;
        gap: 12px;
        align-items: start;
        padding: 15px;
        border: 1px solid #e4eaf2;
        border-radius: 15px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15,23,42,.04);
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }
    a.dth-hr-kpi-card:hover { transform: translateY(-2px); border-color: rgba(var(--tone),.23); box-shadow: 0 13px 30px rgba(15,23,42,.07); }
    .dth-hr-kpi-card[data-tone="violet"] { --tone: 124,58,237; }
    .dth-hr-kpi-card[data-tone="amber"] { --tone: 217,119,6; }
    .dth-hr-kpi-card[data-tone="green"] { --tone: 22,163,74; }
    .dth-hr-kpi-icon { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 13px; color: rgb(var(--tone)); background: rgba(var(--tone),.09); }
    .dth-hr-kpi-icon svg { width: 22px; height: 22px; }
    .dth-hr-kpi-body { min-width: 0; }
    .dth-hr-kpi-label { color: #758197; font-size: .73rem; font-weight: 760; text-transform: uppercase; letter-spacing: .035em; }
    .dth-hr-kpi-value { margin-top: .14rem; color: #111827; font-size: 1.52rem; font-weight: 840; letter-spacing: -.035em; }
    .dth-hr-kpi-help { margin-top: .22rem; color: #8d98aa; font-size: .67rem; line-height: 1.35; }
    .dth-hr-kpi-arrow { display: grid; place-items: center; width: 26px; height: 26px; border-radius: 9px; color: rgba(var(--tone),.8); background: rgba(var(--tone),.07); opacity: .75; }
    .dth-hr-kpi-arrow svg { width: 14px; height: 14px; }
    .dth-hr-kpi-glow { position: absolute; width: 82px; height: 82px; right: -38px; bottom: -43px; border-radius: 999px; background: rgba(var(--tone),.09); }

    .dth-hr-dashboard-grid { display: grid; gap: 14px; }
    .dth-hr-dashboard-grid--2 { grid-template-columns: repeat(2, minmax(0,1fr)); }
    .dth-hr-dashboard-card { min-width: 0; border: 1px solid #e4eaf2; border-radius: 16px; background: #fff; box-shadow: 0 8px 24px rgba(15,23,42,.04); padding: 16px; }
    .dth-hr-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 12px; }
    .dth-hr-card-head h3 { margin: 0; color: #172033; font-size: .95rem; font-weight: 820; letter-spacing: -.015em; }
    .dth-hr-card-head p { margin: .25rem 0 0; color: #8a95a8; font-size: .68rem; line-height: 1.4; }
    .dth-hr-card-icon { --tone: 37,99,235; flex: 0 0 auto; width: 36px; height: 36px; display: grid; place-items: center; border-radius: 11px; color: rgb(var(--tone)); background: rgba(var(--tone),.08); }
    .dth-hr-card-icon[data-tone="violet"] { --tone: 124,58,237; }
    .dth-hr-card-icon[data-tone="amber"] { --tone: 217,119,6; }
    .dth-hr-card-icon[data-tone="green"] { --tone: 22,163,74; }
    .dth-hr-card-icon svg { width: 18px; height: 18px; }

    .dth-hr-status-list { display: flex; flex-direction: column; }
    .dth-hr-status-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; min-height: 44px; padding: .5rem .15rem; border-bottom: 1px solid #f0f3f7; }
    .dth-hr-status-row:last-child { border-bottom: 0; }
    .dth-hr-status-row strong { color: #344054; font-size: .84rem; font-weight: 800; }
    .dth-hr-status-row--summary { margin-top: .25rem; padding: .7rem .75rem; border: 1px solid #edf1f6; border-radius: 11px; background: #fafcff; }
    .dth-hr-summary-label { display: inline-flex; align-items: center; gap: .5rem; color: #5d6b80; font-size: .74rem; font-weight: 720; }
    .dth-hr-summary-label svg { width: 16px; height: 16px; color: #2563eb; }

    .dth-hr-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: .24rem .58rem; font-size: .69rem; font-weight: 760; white-space: nowrap; }
    .dth-hr-badge--success { background: #dcfce7; color: #15803d; }
    .dth-hr-badge--warning { background: #fef3c7; color: #b45309; }
    .dth-hr-badge--danger { background: #fee2e2; color: #b91c1c; }
    .dth-hr-badge--info { background: #dbeafe; color: #1d4ed8; }
    .dth-hr-badge--primary { background: #dbeafe; color: #1d4ed8; }
    .dth-hr-badge--gray { background: #f3f4f6; color: #4b5563; }

    .dth-hr-department-list { display: flex; flex-direction: column; gap: 13px; }
    .dth-hr-department-item { padding: 10px 11px; border: 1px solid #edf1f6; border-radius: 12px; background: #fbfcfe; }
    .dth-hr-department-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .dth-hr-department-name { color: #315ec9; font-size: .79rem; font-weight: 790; }
    .dth-hr-department-name:hover { color: #1d4ed8; text-decoration: underline; text-underline-offset: 2px; }
    .dth-hr-department-count { display: flex; align-items: baseline; gap: .3rem; white-space: nowrap; }
    .dth-hr-department-count strong { color: #344054; font-size: .8rem; }
    .dth-hr-department-count span { color: #98a2b3; font-size: .62rem; }
    .dth-hr-progress { height: .38rem; margin-top: .48rem; overflow: hidden; border-radius: 999px; background: #e9eef5; }
    .dth-hr-progress > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg,#3977ef,#6a98f3); }
    .dth-hr-muted { color: #8a95a8; font-size: .67rem; line-height: 1.45; }

    .dth-hr-table-wrap { overflow: auto; }
    .dth-hr-data-table { width: 100%; border-collapse: collapse; font-size: .75rem; }
    .dth-hr-data-table th { padding: .58rem .55rem; text-align: left; color: #8a95a8; font-size: .62rem; font-weight: 780; text-transform: uppercase; letter-spacing: .035em; border-bottom: 1px solid #edf1f6; white-space: nowrap; }
    .dth-hr-data-table td { padding: .66rem .55rem; color: #475467; border-bottom: 1px solid #f1f4f7; vertical-align: middle; white-space: nowrap; }
    .dth-hr-data-table tr:last-child td { border-bottom: 0; }
    .dth-hr-data-table tbody tr:hover { background: #fafcff; }
    .dth-hr-data-table td strong { color: #253047; font-weight: 760; }
    .dth-hr-data-table td small { display: block; margin-top: .1rem; color: #98a2b3; font-size: .61rem; }
    .dth-hr-record-link { color: #2563eb; font-weight: 790; }
    .dth-hr-record-link:hover { color: #1d4ed8; text-decoration: underline; text-underline-offset: 2px; }
    .dth-hr-empty { padding: 1.5rem !important; text-align: center !important; color: #98a2b3 !important; font-size: .75rem !important; }

    .dth-hr-principle-grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 10px; }
    .dth-hr-principle-card { --tone: 37,99,235; display: grid; grid-template-columns: 38px minmax(0,1fr); gap: 11px; padding: 12px; border: 1px solid #edf1f6; border-radius: 12px; background: #fbfcfe; }
    .dth-hr-principle-card[data-tone="violet"] { --tone: 124,58,237; }
    .dth-hr-principle-card[data-tone="green"] { --tone: 22,163,74; }
    .dth-hr-principle-icon { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; color: rgb(var(--tone)); background: rgba(var(--tone),.08); }
    .dth-hr-principle-icon svg { width: 18px; height: 18px; }
    .dth-hr-principle-title { margin-bottom: .22rem; color: #344054; font-size: .73rem; font-weight: 800; }

    .dark .dth-hr-kpi-card,
    .dark .dth-hr-dashboard-card { background: #111827; border-color: #334155; }
    .dark .dth-hr-kpi-value,
    .dark .dth-hr-card-head h3,
    .dark .dth-hr-status-row strong,
    .dark .dth-hr-department-count strong,
    .dark .dth-hr-data-table td strong,
    .dark .dth-hr-principle-title { color: #f1f5f9; }
    .dark .dth-hr-kpi-label,
    .dark .dth-hr-kpi-help,
    .dark .dth-hr-card-head p,
    .dark .dth-hr-muted,
    .dark .dth-hr-data-table th,
    .dark .dth-hr-data-table td { color: #94a3b8; }
    .dark .dth-hr-status-row { border-color: #243244; }
    .dark .dth-hr-status-row--summary,
    .dark .dth-hr-department-item,
    .dark .dth-hr-principle-card { background: #172033; border-color: #334155; }
    .dark .dth-hr-data-table td,
    .dark .dth-hr-data-table th { border-color: #243244; }
    .dark .dth-hr-data-table tbody tr:hover { background: #172033; }

    @media (max-width: 1120px) {
        .dth-hr-kpi-grid { grid-template-columns: repeat(2,minmax(0,1fr)); }
    }
    @media (max-width: 900px) {
        .dth-hr-dashboard-grid--2 { grid-template-columns: 1fr; }
        .dth-hr-principle-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 680px) {
        .dth-hr-kpi-grid { grid-template-columns: 1fr; }
        .dth-hr-kpi-card { min-height: 104px; }
        .dth-hr-card-head { flex-wrap: wrap; }
    }
</style>
</x-filament-panels::page>
