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

<style>
    .dth-hr-shell{display:flex;flex-direction:column;gap:1.5rem}
    .dth-hr-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}
    .dth-hr-kpi{border:1px solid rgb(229 231 235);border-radius:16px;padding:1rem 1.1rem;background:#fff;box-shadow:0 1px 3px rgba(15,23,42,.05);transition:.15s ease}
    .dth-hr-kpi:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(15,23,42,.07)}
    .dark .dth-hr-kpi{background:rgb(17 24 39);border-color:rgb(55 65 81)}
    .dth-hr-kpi-top{display:flex;justify-content:space-between;align-items:center;gap:.8rem}
    .dth-hr-label{font-size:.76rem;font-weight:750;text-transform:uppercase;letter-spacing:.045em;color:rgb(107 114 128)}
    .dth-hr-value{font-size:1.7rem;font-weight:800;line-height:1.2;margin-top:.35rem;color:rgb(17 24 39)}
    .dark .dth-hr-value{color:#fff}
    .dth-hr-help{font-size:.78rem;color:rgb(107 114 128);margin-top:.35rem;min-height:1.15rem}
    .dth-hr-icon{display:flex;align-items:center;justify-content:center;width:2.35rem;height:2.35rem;border-radius:11px;background:rgb(240 253 250);color:rgb(13 148 136)}
    .dark .dth-hr-icon{background:rgba(20,184,166,.14);color:rgb(94 234 212)}
    .dth-hr-grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.25rem}
    .dth-hr-status{display:flex;flex-direction:column;gap:.7rem}
    .dth-hr-status-row,.dth-hr-dept-head{display:flex;align-items:center;justify-content:space-between;gap:1rem}
    .dth-hr-status-row{padding:.55rem .15rem;border-bottom:1px solid rgb(243 244 246)}
    .dark .dth-hr-status-row{border-color:rgb(55 65 81)}
    .dth-hr-badge{display:inline-flex;align-items:center;border-radius:999px;padding:.22rem .58rem;font-size:.75rem;font-weight:700}
    .dth-hr-badge--success{background:rgb(220 252 231);color:rgb(21 128 61)}
    .dth-hr-badge--warning{background:rgb(254 243 199);color:rgb(180 83 9)}
    .dth-hr-badge--gray{background:rgb(243 244 246);color:rgb(75 85 99)}
    .dark .dth-hr-badge--success{background:rgba(34,197,94,.14);color:rgb(134 239 172)}
    .dark .dth-hr-badge--warning{background:rgba(245,158,11,.14);color:rgb(253 230 138)}
    .dark .dth-hr-badge--gray{background:rgb(55 65 81);color:rgb(209 213 219)}
    .dth-hr-depts{display:flex;flex-direction:column;gap:.9rem}.dth-hr-dept-name{font-size:.86rem;font-weight:700}
    .dth-hr-progress{height:.38rem;border-radius:999px;background:rgb(229 231 235);overflow:hidden;margin-top:.38rem}.dark .dth-hr-progress{background:rgb(55 65 81)}
    .dth-hr-progress>span{display:block;height:100%;border-radius:inherit;background:rgb(20 184 166)}
    .dth-hr-muted{font-size:.78rem;color:rgb(107 114 128)}
    .dth-hr-table-wrap{overflow-x:auto}.dth-hr-table{width:100%;border-collapse:collapse;font-size:.86rem}
    .dth-hr-table th{font-size:.71rem;text-transform:uppercase;letter-spacing:.04em;color:rgb(107 114 128);text-align:left;padding:.65rem .7rem;border-bottom:1px solid rgb(229 231 235);white-space:nowrap}
    .dth-hr-table td{padding:.72rem .7rem;border-bottom:1px solid rgb(243 244 246);vertical-align:middle;white-space:nowrap}.dark .dth-hr-table th,.dark .dth-hr-table td{border-color:rgb(55 65 81)}
    .dth-hr-principle{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.85rem}.dth-hr-principle-card{border:1px solid rgb(229 231 235);border-radius:13px;padding:1rem;background:rgb(249 250 251)}.dark .dth-hr-principle-card{background:rgb(31 41 55);border-color:rgb(55 65 81)}
    .dth-hr-principle-title{font-size:.86rem;font-weight:800;margin-bottom:.25rem}.dth-hr-empty{text-align:center;color:rgb(107 114 128);padding:1rem}
    @media(max-width:1100px){.dth-hr-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.dth-hr-principle{grid-template-columns:1fr}}
    @media(max-width:760px){.dth-hr-kpis,.dth-hr-grid2{grid-template-columns:1fr}}
</style>

<x-filament-panels::page>
    <div class="dth-hr-shell">
        <div class="dth-hr-kpis">
            <a href="{{ EmployeeResource::getUrl('index') }}" class="dth-hr-kpi">
                <div class="dth-hr-kpi-top"><div><div class="dth-hr-label">{{ UiText::get('dashboard.metrics.employees', 'Employees') }}</div><div class="dth-hr-value">{{ $number($m['employees'] ?? 0) }}</div></div><div class="dth-hr-icon"><x-filament::icon icon="heroicon-o-users" class="h-5 w-5" /></div></div>
                <div class="dth-hr-help">{{ UiText::get('dashboard.help.active_employees', ':count currently active', ['count' => $number($m['active_employees'] ?? 0)]) }}</div>
            </a>
            <a href="{{ DepartmentResource::getUrl('index') }}" class="dth-hr-kpi">
                <div class="dth-hr-kpi-top"><div><div class="dth-hr-label">{{ UiText::get('dashboard.metrics.departments', 'Departments') }}</div><div class="dth-hr-value">{{ $number($m['departments'] ?? 0) }}</div></div><div class="dth-hr-icon"><x-filament::icon icon="heroicon-o-building-office-2" class="h-5 w-5" /></div></div>
                <div class="dth-hr-help">{{ UiText::get('dashboard.help.organization', 'Organization structure managed by HR') }}</div>
            </a>
            <div class="dth-hr-kpi">
                <div class="dth-hr-kpi-top"><div><div class="dth-hr-label">{{ UiText::get('dashboard.metrics.positions', 'Job titles') }}</div><div class="dth-hr-value">{{ $number($m['positions'] ?? 0) }}</div></div><div class="dth-hr-icon"><x-filament::icon icon="heroicon-o-briefcase" class="h-5 w-5" /></div></div>
                <div class="dth-hr-help">{{ UiText::get('dashboard.help.positions', 'Reusable job-title master data') }}</div>
            </div>
            <div class="dth-hr-kpi">
                <div class="dth-hr-kpi-top"><div><div class="dth-hr-label">{{ UiText::get('dashboard.metrics.on_leave', 'Unavailable today') }}</div><div class="dth-hr-value">{{ $number($m['on_leave'] ?? 0) }}</div></div><div class="dth-hr-icon"><x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5" /></div></div>
                <div class="dth-hr-help">{{ UiText::get('dashboard.help.on_leave', 'Leave, sick leave or absence') }}</div>
            </div>
        </div>

        <div class="dth-hr-grid2">
            <x-filament::section :heading="UiText::get('dashboard.sections.employment_status', 'Employment status')" icon="heroicon-o-identification">
                <div class="dth-hr-status">
                    @foreach(EmploymentStatus::cases() as $status)
                        @php $count = (int)($snapshot['employment_statuses'][$status->value] ?? 0); @endphp
                        <div class="dth-hr-status-row"><span class="dth-hr-badge dth-hr-badge--{{ $status->color() }}">{{ $status->label() }}</span><strong>{{ $number($count) }}</strong></div>
                    @endforeach
                    <div class="dth-hr-status-row"><span>{{ UiText::get('dashboard.metrics.linked_users', 'Linked login accounts') }}</span><strong>{{ $number($m['linked_users'] ?? 0) }}</strong></div>
                </div>
            </x-filament::section>

            <x-filament::section :heading="UiText::get('dashboard.sections.departments', 'Active employees by department')" icon="heroicon-o-building-office">
                <div class="dth-hr-depts">
                    @forelse($snapshot['departments'] ?? [] as $department)
                        @php
                            $width = max(3, round(((int)$department['count'] / $maxDepartment) * 100));
                            $function = $department['function'] ? BusinessFunction::tryFrom($department['function']) : null;
                        @endphp
                        <div>
                            <div class="dth-hr-dept-head"><a class="dth-hr-dept-name text-primary-600 dark:text-primary-400" href="{{ DepartmentResource::getUrl('edit', ['record' => $department['id']]) }}">{{ $department['name'] }}</a><span class="dth-hr-muted">{{ $number($department['count']) }} {{ UiText::get('models.employee_plural_short', 'employees') }}</span></div>
                            <div class="dth-hr-progress"><span style="width:{{ $width }}%"></span></div>
                            @if($function)<div class="dth-hr-muted mt-1">{{ $function->label() }}</div>@endif
                        </div>
                    @empty
                        <div class="dth-hr-empty">{{ UiText::get('dashboard.empty.no_departments', 'No departments yet.') }}</div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section :heading="UiText::get('dashboard.sections.recent_employees', 'Recently added employees')" icon="heroicon-o-clock">
            <div class="dth-hr-table-wrap"><table class="dth-hr-table"><thead><tr><th>{{ UiText::get('fields.employee_code', 'Employee code') }}</th><th>{{ UiText::get('fields.full_name', 'Full name') }}</th><th>{{ UiText::get('fields.department', 'Department') }}</th><th>{{ UiText::get('fields.position', 'Job title') }}</th><th>{{ UiText::get('common.fields.status', 'Status') }}</th></tr></thead><tbody>
                @forelse($snapshot['recent_employees'] ?? [] as $employee)
                    @php $status = EmploymentStatus::tryFrom((string)$employee['status']); @endphp
                    <tr><td><a class="font-semibold text-primary-600 dark:text-primary-400" href="{{ EmployeeResource::getUrl('view', ['record' => $employee['id']]) }}">{{ $employee['code'] }}</a><div class="dth-hr-muted">{{ optional($employee['created_at'])->format('d/m/Y') }}</div></td><td>{{ $employee['name'] }}</td><td>{{ $employee['department'] ?: '—' }}</td><td>{{ $employee['position'] ?: '—' }}</td><td><span class="dth-hr-badge dth-hr-badge--{{ $status?->color() ?? 'gray' }}">{{ $status?->label() ?? $employee['status'] }}</span></td></tr>
                @empty
                    <tr><td colspan="5" class="dth-hr-empty">{{ UiText::get('dashboard.empty.no_employees', 'No employees yet.') }}</td></tr>
                @endforelse
            </tbody></table></div>
        </x-filament::section>

        <x-filament::section :heading="UiText::get('dashboard.sections.boundary', 'Module ownership rule')" icon="heroicon-o-squares-2x2">
            <div class="dth-hr-principle">
                <div class="dth-hr-principle-card"><div class="dth-hr-principle-title">{{ UiText::get('dashboard.boundary.hr_title', 'Human Resource owns identity') }}</div><div class="dth-hr-muted">{{ UiText::get('dashboard.boundary.hr_body', 'Employee profile, department, job title, employment status and availability are maintained here once.') }}</div></div>
                <div class="dth-hr-principle-card"><div class="dth-hr-principle-title">{{ UiText::get('dashboard.boundary.crm_title', 'CRM owns CRM capacity') }}</div><div class="dth-hr-muted">{{ UiText::get('dashboard.boundary.crm_body', 'CRM only attaches Lead/Customer capacity, distribution weight and assignment eligibility to an HR employee.') }}</div></div>
                <div class="dth-hr-principle-card"><div class="dth-hr-principle-title">{{ UiText::get('dashboard.boundary.no_duplicate_title', 'No duplicated employee master') }}</div><div class="dth-hr-muted">{{ UiText::get('dashboard.boundary.no_duplicate_body', 'Email, Marketing and future modules reuse the HR employee instead of creating separate staff records.') }}</div></div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
