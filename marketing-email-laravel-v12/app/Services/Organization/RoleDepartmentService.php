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
        if ($role === null || $role === '') {
            return null;
        }

        $resolved = $role instanceof UserRole
            ? $role
            : UserRole::tryFrom($role);

        return $resolved?->requiredDepartmentFunction();
    }

    public function requiresStaff(UserRole|string|null $role): bool
    {
        return $this->requiredFunction($role) !== null;
    }

    public function isCompatible(UserRole|string|null $role, ?Department $department): bool
    {
        $required = $this->requiredFunction($role);

        if ($required === null) {
            return true;
        }

        return $department?->function_key === $required->value;
    }

    public function assertCompatible(UserRole|string|null $role, ?Staff $staff): void
    {
        $required = $this->requiredFunction($role);

        if ($required === null) {
            return;
        }

        if ($staff === null) {
            throw ValidationException::withMessages([
                'staff_id' => __('validation.staff_required_for_role'),
            ]);
        }

        $staff->loadMissing('department');

        if (! $this->isCompatible($role, $staff->department)) {
            throw ValidationException::withMessages([
                'role' => __('validation.role_department_mismatch', [
                    'department' => $staff->department?->name ?? __('common.not_available'),
                    'function' => $required->label(),
                ]),
            ]);
        }
    }
}
