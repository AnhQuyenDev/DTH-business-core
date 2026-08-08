<?php

namespace App\Services\Organization;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\UserRole;
use App\Models\Crm\Department;
use App\Models\Crm\Staff;
use Illuminate\Validation\ValidationException;

class RoleDepartmentService
{
    public function requiredFunction(UserRole|string|null $role): ?DepartmentFunction
    {
        $resolved = $this->resolveRole($role);

        return $resolved?->requiredDepartmentFunction();
    }

    public function requiresStaff(UserRole|string|null $role): bool
    {
        return $this->resolveRole($role)?->requiresStaff() ?? false;
    }

    public function isCompatible(UserRole|string|null $role, ?Department $department): bool
    {
        $required = $this->requiredFunction($role);

        // Vai trò mới không chứa ý nghĩa phòng ban.
        if ($required === null) {
            return true;
        }

        return $department?->function_key === $required->value;
    }

    public function assertCompatible(UserRole|string|null $role, ?Staff $staff): void
    {
        $resolved = $this->resolveRole($role);

        if ($resolved === null) {
            return;
        }

        if ($resolved->requiresStaff() && $staff === null) {
            throw ValidationException::withMessages([
                'staff_id' => __('validation.staff_required_for_role'),
            ]);
        }

        if ($staff === null) {
            return;
        }

        $required = $resolved->requiredDepartmentFunction();

        // admin/executive/user/viewer không bị gắn cứng với phòng ban.
        if ($required === null) {
            return;
        }

        $staff->loadMissing('department');

        if (! $this->isCompatible($resolved, $staff->department)) {
            throw ValidationException::withMessages([
                'role' => __('validation.role_department_mismatch', [
                    'department' => $staff->department?->name ?? __('common.not_available'),
                    'function' => $required->label(),
                ]),
            ]);
        }
    }

    private function resolveRole(UserRole|string|null $role): ?UserRole
    {
        if ($role === null || $role === '') {
            return null;
        }

        return $role instanceof UserRole
            ? $role
            : UserRole::tryFrom($role);
    }
}
