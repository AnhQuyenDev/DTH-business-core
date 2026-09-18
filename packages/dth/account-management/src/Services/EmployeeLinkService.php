<?php
namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Models\AccountUser;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class EmployeeLinkService
{
    public function available(): bool
    {
        $model = (string) config('dth-account-management.human_resource.employee_model');
        return class_exists($model) && Schema::hasTable('hr_employees');
    }

    public function options(?int $currentUserId = null): array
    {
        if (! $this->available()) return [];
        $model = (string) config('dth-account-management.human_resource.employee_model');
        return $model::query()
            ->where(function ($query) use ($currentUserId): void {
                $query->whereNull('user_id');
                if ($currentUserId) $query->orWhere('user_id', $currentUserId);
            })
            ->orderBy('full_name')
            ->get(['id', 'employee_code', 'full_name', 'email'])
            ->mapWithKeys(fn ($employee): array => [$employee->id => trim($employee->employee_code.' · '.$employee->full_name)])
            ->all();
    }

    public function employeeIdForUser(int $userId): ?int
    {
        if (! $this->available()) return null;
        $model = (string) config('dth-account-management.human_resource.employee_model');
        return $model::query()->where('user_id', $userId)->value('id');
    }

    public function labelForUser(int $userId): ?string
    {
        if (! $this->available()) return null;
        $model = (string) config('dth-account-management.human_resource.employee_model');
        $employee = $model::query()->where('user_id', $userId)->first(['employee_code','full_name']);
        return $employee ? trim($employee->employee_code.' · '.$employee->full_name) : null;
    }

    public function link(AccountUser $user, ?int $employeeId): void
    {
        if (! $this->available()) return;
        $model = (string) config('dth-account-management.human_resource.employee_model');
        $model::query()->where('user_id', $user->id)->update(['user_id' => null]);
        if ($employeeId) {
            $model::query()->whereKey($employeeId)->where(function ($query) use ($user): void {
                $query->whereNull('user_id')->orWhere('user_id', $user->id);
            })->update(['user_id' => $user->id]);
        }
    }
}
