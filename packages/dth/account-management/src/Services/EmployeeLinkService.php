<?php

namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Models\AccountUser;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

final class EmployeeLinkService
{
    private const REQUEST_KEY = 'account_admin_request';

    public function available(): bool
    {
        $model = (string) config('dth-account-management.human_resource.employee_model');

        return class_exists($model) && Schema::hasTable('hr_employees');
    }

    public function options(?int $currentUserId = null): array
    {
        if (! $this->available()) {
            return [];
        }

        if ($currentUserId) {
            $currentUser = AccountUser::query()->find($currentUserId);
            if ($currentUser && $this->isProtectedAccount($currentUser)) {
                return [];
            }
        }

        $model = (string) config('dth-account-management.human_resource.employee_model');

        return $model::query()
            ->where(function ($query) use ($currentUserId): void {
                $query->whereNull('user_id');
                if ($currentUserId) {
                    $query->orWhere('user_id', $currentUserId);
                }
            })
            ->orderBy('full_name')
            ->get(['id', 'employee_code', 'full_name', 'email'])
            ->mapWithKeys(fn ($employee): array => [
                $employee->id => trim($employee->employee_code.' · '.$employee->full_name),
            ])
            ->all();
    }

    public function employeeIdForUser(int $userId): ?int
    {
        if (! $this->available()) {
            return null;
        }

        $model = (string) config('dth-account-management.human_resource.employee_model');

        return $model::query()->where('user_id', $userId)->value('id');
    }

    public function labelForUser(int $userId): ?string
    {
        if (! $this->available()) {
            return null;
        }

        $model = (string) config('dth-account-management.human_resource.employee_model');
        $employee = $model::query()->where('user_id', $userId)->first(['employee_code', 'full_name']);

        return $employee ? trim($employee->employee_code.' · '.$employee->full_name) : null;
    }

    public function requestedEmployeeId(): ?int
    {
        if (! $this->available()) {
            return null;
        }

        $employeeId = (int) request()->query('employee_id', 0);
        if ($employeeId <= 0) {
            return null;
        }

        $model = (string) config('dth-account-management.human_resource.employee_model');
        $employee = $model::query()->whereKey($employeeId)->whereNull('user_id')->first(['id']);

        return $employee ? (int) $employee->id : null;
    }

    public function prefill(string $field): ?string
    {
        $employeeId = $this->requestedEmployeeId();
        if (! $employeeId) {
            return null;
        }

        $model = (string) config('dth-account-management.human_resource.employee_model');
        $employee = $model::query()->find($employeeId, ['id', 'full_name', 'email', 'phone']);
        if (! $employee) {
            return null;
        }

        return match ($field) {
            'name' => $employee->full_name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            default => null,
        };
    }

    /**
     * Requests raised by Human Resource that require an Account administrator.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pendingHrRequests(): array
    {
        if (! $this->available()) {
            return [];
        }

        $model = (string) config('dth-account-management.human_resource.employee_model');
        $resource = 'Dth\\AccountManagement\\Filament\\Resources\\AccountUserResource';

        return $model::query()
            ->whereNotNull('metadata')
            ->orderBy('full_name')
            ->get(['id', 'employee_code', 'full_name', 'email', 'phone', 'user_id', 'metadata'])
            ->map(function ($employee) use ($resource): ?array {
                $request = data_get($employee->metadata ?? [], self::REQUEST_KEY);
                if (! is_array($request) || ($request['status'] ?? null) !== 'pending') {
                    return null;
                }

                $type = (string) ($request['type'] ?? 'provision_account');
                $url = null;

                if (class_exists($resource)) {
                    try {
                        $url = $type === 'sync_account_identity' && $employee->user_id
                            ? $resource::getUrl('edit', ['record' => (int) $employee->user_id])
                            : $resource::getUrl('create', ['employee_id' => (int) $employee->id]);
                    } catch (Throwable) {
                        $url = null;
                    }
                }

                return [
                    'employee_id' => (int) $employee->id,
                    'employee_code' => (string) $employee->employee_code,
                    'full_name' => (string) $employee->full_name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'user_id' => $employee->user_id ? (int) $employee->user_id : null,
                    'type' => $type,
                    'requested_at' => $request['requested_at'] ?? null,
                    'requested_by_name' => $request['requested_by_name'] ?? null,
                    'requested_name' => $request['requested_name'] ?? $employee->full_name,
                    'requested_email' => $request['requested_email'] ?? $employee->email,
                    'requested_phone' => $request['requested_phone'] ?? $employee->phone,
                    'differences' => (array) ($request['differences'] ?? []),
                    'note' => $request['note'] ?? null,
                    'action_url' => $url,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function link(AccountUser $user, ?int $employeeId): void
    {
        if (! $this->available()) {
            return;
        }

        if ($employeeId && $this->isProtectedAccount($user)) {
            throw ValidationException::withMessages([
                'employee_id' => 'Tài khoản quản trị hệ thống được bảo vệ không thể liên kết với hồ sơ nhân viên.',
            ]);
        }

        $model = (string) config('dth-account-management.human_resource.employee_model');
        $model::query()->where('user_id', $user->id)->update(['user_id' => null]);

        if (! $employeeId) {
            return;
        }

        $employee = $model::query()->whereKey($employeeId)->first();
        if (! $employee) {
            return;
        }

        $linked = $model::query()
            ->whereKey($employeeId)
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->update(['user_id' => $user->id]);

        if ($linked) {
            $this->resolveProvisionRequestForEmployee($employeeId);
        }
    }

    public function resolveIdentityRequestIfSatisfied(AccountUser $user): void
    {
        if (! $this->available()) {
            return;
        }

        $model = (string) config('dth-account-management.human_resource.employee_model');
        $employee = $model::query()->where('user_id', $user->id)->first();
        if (! $employee) {
            return;
        }

        $request = data_get($employee->metadata ?? [], self::REQUEST_KEY);
        if (! is_array($request)
            || ($request['status'] ?? null) !== 'pending'
            || ($request['type'] ?? null) !== 'sync_account_identity') {
            return;
        }

        $nameOk = blank($request['requested_name'] ?? null)
            || trim((string) $request['requested_name']) === trim((string) $user->name);
        $emailOk = blank($request['requested_email'] ?? null)
            || strtolower(trim((string) $request['requested_email'])) === strtolower(trim((string) $user->email));
        $phoneOk = blank($request['requested_phone'] ?? null)
            || preg_replace('/\D+/', '', (string) $request['requested_phone']) === preg_replace('/\D+/', '', (string) $user->phone);

        if ($nameOk && $emailOk && $phoneOk) {
            $this->resolveRequestForEmployee((int) $employee->id, 'identity_synchronized');
        }
    }

    private function isProtectedAccount(AccountUser $user): bool
    {
        if (! Schema::hasTable('account_roles') || ! Schema::hasTable('account_role_user')) {
            return false;
        }

        $keys = (array) config('dth-account-management.human_resource.protected_role_keys', [
            'super-admin', 'super_admin', 'administrator', 'system_admin', 'system-administrator', 'system_administrator',
        ]);

        return $user->roles()->whereIn('key', $keys)->exists();
    }

    private function resolveProvisionRequestForEmployee(int $employeeId): void
    {
        $model = (string) config('dth-account-management.human_resource.employee_model');
        $employee = $model::query()->find($employeeId);
        if (! $employee) {
            return;
        }

        $request = data_get($employee->metadata ?? [], self::REQUEST_KEY);
        if (! is_array($request)
            || ($request['status'] ?? null) !== 'pending'
            || ($request['type'] ?? null) !== 'provision_account') {
            return;
        }

        $this->resolveRequestForEmployee($employeeId, 'account_linked');
    }

    private function resolveRequestForEmployee(int $employeeId, string $resolution): void
    {
        $model = (string) config('dth-account-management.human_resource.employee_model');
        $employee = $model::query()->find($employeeId);
        if (! $employee) {
            return;
        }

        $metadata = is_array($employee->metadata) ? $employee->metadata : [];
        $request = $metadata[self::REQUEST_KEY] ?? null;
        if (! is_array($request)) {
            return;
        }

        $request['status'] = 'resolved';
        $request['resolved_at'] = now()->toIso8601String();
        $request['resolution'] = $resolution;
        $metadata[self::REQUEST_KEY] = $request;
        $employee->forceFill(['metadata' => $metadata])->save();
    }
}
