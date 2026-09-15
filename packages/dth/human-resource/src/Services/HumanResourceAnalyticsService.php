<?php

namespace Dth\HumanResource\Services;

use Dth\HumanResource\Enums\EmploymentStatus;
use Dth\HumanResource\Models\Department;
use Dth\HumanResource\Models\Employee;
use Dth\HumanResource\Models\EmployeeAvailability;
use Dth\HumanResource\Models\Position;

final class HumanResourceAnalyticsService
{
    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $now = now();

        return [
            'metrics' => [
                'employees' => Employee::query()->count(),
                'active_employees' => Employee::query()->where('employment_status', EmploymentStatus::Active->value)->count(),
                'inactive_employees' => Employee::query()->where('employment_status', EmploymentStatus::Inactive->value)->count(),
                'resigned_employees' => Employee::query()->where('employment_status', EmploymentStatus::Resigned->value)->count(),
                'departments' => Department::query()->where('is_active', true)->count(),
                'positions' => Position::query()->where('is_active', true)->count(),
                'linked_users' => Employee::query()->whereNotNull('user_id')->count(),
                'on_leave' => EmployeeAvailability::query()
                    ->where('starts_at', '<=', $now)
                    ->where('ends_at', '>=', $now)
                    ->whereIn('status', ['leave', 'sick', 'absent'])
                    ->distinct('employee_id')
                    ->count('employee_id'),
            ],
            'employment_statuses' => Employee::query()
                ->selectRaw('employment_status, COUNT(*) as aggregate')
                ->groupBy('employment_status')
                ->pluck('aggregate', 'employment_status')
                ->map(fn ($value): int => (int) $value)
                ->all(),
            'departments' => Department::query()
                ->withCount(['employees' => fn ($query) => $query->where('employment_status', EmploymentStatus::Active->value)])
                ->where('is_active', true)
                ->orderByDesc('employees_count')
                ->orderBy('sort_order')
                ->limit(8)
                ->get()
                ->map(fn (Department $department): array => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'count' => (int) $department->employees_count,
                    'function' => $department->function_key?->value,
                ])
                ->all(),
            'recent_employees' => Employee::query()
                ->with(['department:id,name', 'position:id,title'])
                ->latest('id')
                ->limit(6)
                ->get()
                ->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'code' => $employee->employee_code,
                    'name' => $employee->full_name,
                    'department' => $employee->department?->name,
                    'position' => $employee->position?->title,
                    'status' => $employee->employment_status?->value ?? (string) $employee->getRawOriginal('employment_status'),
                    'created_at' => $employee->created_at,
                ])
                ->all(),
        ];
    }
}
