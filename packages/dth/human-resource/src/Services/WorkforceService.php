<?php

namespace Dth\HumanResource\Services;

use Dth\HumanResource\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

final class WorkforceService
{
    public function activeEmployees(): Builder
    {
        return Employee::query()->active();
    }

    public function findByUserId(?int $userId): ?Employee
    {
        return $userId ? Employee::query()->where('user_id', $userId)->first() : null;
    }

    public function canReceiveNewWork(Employee|int $employee, ?\DateTimeInterface $at = null): bool
    {
        $employee = $employee instanceof Employee ? $employee : Employee::query()->find($employee);

        return $employee?->isAvailableForNewWork($at) ?? false;
    }
}
