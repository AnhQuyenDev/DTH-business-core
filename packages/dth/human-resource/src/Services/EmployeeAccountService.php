<?php

namespace Dth\HumanResource\Services;

use Dth\HumanResource\Models\Employee;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class EmployeeAccountService
{
    private const REQUEST_KEY = 'account_admin_request';

    public function available(): bool
    {
        return (bool) config('dth-account-management.enabled', true)
            && class_exists('Dth\\AccountManagement\\Models\\AccountUser')
            && Schema::hasTable('users')
            && Schema::hasTable('hr_employees');
    }

    public function canManageAccounts(): bool
    {
        return $this->allowsDefinedAbility('accounts.users.manage');
    }

    public function canViewAccounts(): bool
    {
        return $this->allowsDefinedAbility('accounts.view');
    }

    public function accountSummary(?Employee $employee): string
    {
        if (! $employee?->exists) {
            return 'Chưa cấp tài khoản';
        }

        if (! $employee->user_id) {
            return $this->hasPendingAdminRequest($employee)
                ? 'Đang chờ quản trị viên xử lý yêu cầu tài khoản'
                : 'Chưa cấp tài khoản';
        }

        $user = $this->findUser((int) $employee->user_id);
        if (! $user) {
            return 'Liên kết tài khoản không còn tồn tại';
        }

        $suffix = $this->hasPendingAdminRequest($employee) ? ' · Chờ quản trị viên đồng bộ danh tính' : '';

        return trim((string) $user->name).' · '.(string) $user->email.$suffix;
    }

    public function accountStatus(Employee $employee): string
    {
        if ($employee->user_id) {
            return $this->hasPendingAdminRequest($employee) ? 'linked_pending_sync' : 'linked';
        }

        return $this->hasPendingAdminRequest($employee) ? 'pending' : 'unlinked';
    }

    public function accountStatusLabel(Employee $employee): string
    {
        return match ($this->accountStatus($employee)) {
            'linked' => 'Đã cấp tài khoản',
            'linked_pending_sync' => 'Đã liên kết · chờ đồng bộ',
            'pending' => 'Đang chờ cấp',
            default => 'Chưa cấp tài khoản',
        };
    }

    public function accountStatusColor(Employee $employee): string
    {
        return match ($this->accountStatus($employee)) {
            'linked' => 'success',
            'linked_pending_sync', 'pending' => 'warning',
            default => 'gray',
        };
    }

    public function linkedAccountEmail(Employee $employee): ?string
    {
        if (! $employee->user_id) {
            return null;
        }

        return $this->findUser((int) $employee->user_id)?->email;
    }

    /** @return array<int, string> */
    public function linkableAccountOptions(Employee $employee): array
    {
        if (! $this->available() || $employee->user_id) {
            return [];
        }

        $model = $this->accountUserModel();
        $usedIds = Employee::query()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $protectedIds = $this->protectedUserIds();
        $excluded = array_values(array_unique([...$usedIds, ...$protectedIds]));

        return $model::query()
            ->when($excluded !== [], fn ($query) => $query->whereNotIn('id', $excluded))
            ->when(
                Schema::hasColumn('users', 'account_status'),
                fn ($query) => $query->whereIn('account_status', ['active', 'pending']),
            )
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'last_login_at'])
            ->filter(function ($user) use ($employee): bool {
                $comparison = $this->compareIdentity($employee, $user);

                // Never repurpose an already-used account for a different person.
                return ! ($comparison['mismatch'] && filled($user->last_login_at));
            })
            ->mapWithKeys(function ($user) use ($employee): array {
                $comparison = $this->compareIdentity($employee, $user);
                $prefix = $comparison['mismatch'] ? 'Cần xác minh' : 'Khớp hồ sơ';

                return [
                    (int) $user->id => $prefix.' · '.trim((string) $user->name).' · '.(string) $user->email,
                ];
            })
            ->all();
    }

    /**
     * @return array{mismatch: bool, differences: array<int, string>}
     */
    public function compareIdentity(Employee $employee, Model $user): array
    {
        $differences = [];

        if (filled($employee->full_name) && filled($user->name)
            && $this->normalizeName((string) $employee->full_name) !== $this->normalizeName((string) $user->name)) {
            $differences[] = 'họ tên';
        }

        if (filled($employee->email) && filled($user->email)
            && strtolower(trim((string) $employee->email)) !== strtolower(trim((string) $user->email))) {
            $differences[] = 'email';
        }

        if (filled($employee->phone) && filled($user->phone)
            && Employee::normalizePhone((string) $employee->phone) !== Employee::normalizePhone((string) $user->phone)) {
            $differences[] = 'số điện thoại';
        }

        return [
            'mismatch' => $differences !== [],
            'differences' => $differences,
        ];
    }

    /**
     * @return array{identity_mismatch: bool, admin_request_created: bool, admin_recipient_count: int}
     */
    public function linkExisting(Employee $employee, int $userId, bool $confirmedMismatch = false, ?string $note = null): array
    {
        if (! $this->available()) {
            throw ValidationException::withMessages([
                'account_user_id' => 'Module Tài khoản & Phân quyền hiện không khả dụng.',
            ]);
        }

        if ($employee->user_id) {
            throw ValidationException::withMessages([
                'account_user_id' => 'Nhân viên này đã được liên kết với một tài khoản.',
            ]);
        }

        $user = $this->findUser($userId);
        if (! $user) {
            throw ValidationException::withMessages([
                'account_user_id' => 'Tài khoản được chọn không còn tồn tại.',
            ]);
        }

        if ($this->isProtectedUser($userId)) {
            throw ValidationException::withMessages([
                'account_user_id' => 'Tài khoản hệ thống hoặc tài khoản quản trị được bảo vệ không thể liên kết với hồ sơ nhân viên.',
            ]);
        }

        $otherEmployee = Employee::query()
            ->where('user_id', $userId)
            ->where($employee->getKeyName(), '!=', $employee->getKey())
            ->first(['id', 'employee_code', 'full_name']);

        if ($otherEmployee) {
            throw ValidationException::withMessages([
                'account_user_id' => 'Tài khoản này đã thuộc về '.$otherEmployee->employee_code.' · '.$otherEmployee->full_name.'.',
            ]);
        }

        $comparison = $this->compareIdentity($employee, $user);
        if ($comparison['mismatch'] && filled($user->last_login_at)) {
            throw ValidationException::withMessages([
                'account_user_id' => 'Tài khoản này đã từng đăng nhập nhưng thông tin danh tính không khớp. Không được tái sử dụng tài khoản đã có lịch sử cho một nhân viên khác; hãy cấp tài khoản mới.',
            ]);
        }

        if ($comparison['mismatch'] && ! $confirmedMismatch) {
            throw ValidationException::withMessages([
                'confirm_identity_mismatch' => 'Thông tin '.implode(', ', $comparison['differences']).' không khớp. Hãy xác minh và đánh dấu xác nhận trước khi liên kết.',
            ]);
        }

        $employee->forceFill(['user_id' => $userId])->save();

        $requestCreated = false;
        $adminRecipientCount = 0;
        if ($comparison['mismatch'] && ! $this->canManageAccounts()) {
            $adminRecipientCount = $this->writeAdminRequest($employee, [
                'type' => 'sync_account_identity',
                'status' => 'pending',
                'account_user_id' => $userId,
                'requested_name' => $employee->full_name,
                'requested_email' => $employee->email,
                'requested_phone' => $employee->phone,
                'differences' => $comparison['differences'],
                'note' => trim((string) $note) ?: 'HR đã xác minh liên kết nhưng không có quyền sửa danh tính tài khoản. Cần quản trị viên kiểm tra và đồng bộ thông tin tài khoản.',
            ]);
            $requestCreated = true;
        } else {
            $this->markAdminRequestResolved($employee, 'linked');
        }

        return [
            'identity_mismatch' => $comparison['mismatch'],
            'admin_request_created' => $requestCreated,
            'admin_recipient_count' => $adminRecipientCount,
        ];
    }

    public function requestProvisioning(Employee $employee, ?string $note = null, ?string $requestedEmail = null): int
    {
        $requestedEmail = strtolower(trim((string) ($requestedEmail ?: $employee->email)));

        if ($requestedEmail === '' || ! filter_var($requestedEmail, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'requested_email' => 'Hãy nhập email đăng nhập hợp lệ để quản trị viên có thể cấp tài khoản.',
            ]);
        }

        if ($employee->user_id) {
            throw ValidationException::withMessages([
                'requested_email' => 'Nhân viên này đã có tài khoản hệ thống.',
            ]);
        }

        if ($this->hasPendingAdminRequest($employee)) {
            throw ValidationException::withMessages([
                'requested_email' => 'Nhân viên này đã có một yêu cầu tài khoản đang chờ xử lý.',
            ]);
        }

        return $this->writeAdminRequest($employee, [
            'type' => 'provision_account',
            'status' => 'pending',
            'requested_name' => $employee->full_name,
            'requested_email' => $requestedEmail,
            'requested_phone' => $employee->phone,
            'note' => trim((string) $note) ?: null,
        ]);
    }

    public function hasPendingAdminRequest(Employee $employee): bool
    {
        $request = data_get($employee->metadata ?? [], self::REQUEST_KEY);

        return is_array($request) && ($request['status'] ?? null) === 'pending';
    }

    public function provisioningUrl(Employee $employee): ?string
    {
        $resource = 'Dth\\AccountManagement\\Filament\\Resources\\AccountUserResource';
        if (! class_exists($resource)) {
            return null;
        }

        try {
            return $resource::getUrl('create', ['employee_id' => $employee->getKey()]);
        } catch (Throwable) {
            return null;
        }
    }

    public function unlink(Employee $employee): void
    {
        if (! $employee->user_id) {
            return;
        }

        $employee->forceFill(['user_id' => null])->save();
    }

    private function writeAdminRequest(Employee $employee, array $payload): int
    {
        $metadata = is_array($employee->metadata) ? $employee->metadata : [];
        $metadata[self::REQUEST_KEY] = array_merge($payload, [
            'requested_at' => now()->toIso8601String(),
            'requested_by_user_id' => auth()->id(),
            'requested_by_name' => auth()->user()?->name,
        ]);

        $employee->forceFill(['metadata' => $metadata])->save();
        $request = (array) $metadata[self::REQUEST_KEY];
        $adminRecipientCount = $this->notifyAccountAdministrators($employee, $request);
        $this->notifyRequesterSubmitted($employee, $request, $adminRecipientCount);

        return $adminRecipientCount;
    }

    /** @param array<string, mixed> $request */
    private function notifyAccountAdministrators(Employee $employee, array $request): int
    {
        $manager = 'Dth\NotificationCenter\Services\NotificationManager';
        if (! class_exists($manager) || ! Schema::hasTable('dth_notifications')) {
            return 0;
        }

        try {
            $resource = 'Dth\AccountManagement\Filament\Resources\AccountUserResource';
            $type = (string) ($request['type'] ?? 'provision_account');
            $isIdentitySync = $type === 'sync_account_identity';
            $actionUrl = null;
            if (class_exists($resource)) {
                $actionUrl = $isIdentitySync && $employee->user_id
                    ? $resource::getUrl('edit', ['record' => (int) $employee->user_id])
                    : $resource::getUrl('create', ['employee_id' => (int) $employee->getKey()]);
            }

            $title = $isIdentitySync ? 'Yêu cầu đồng bộ danh tính tài khoản' : 'Yêu cầu cấp tài khoản mới';
            $body = trim($employee->employee_code.' · '.$employee->full_name)
                .($isIdentitySync ? ' cần quản trị viên kiểm tra và đồng bộ thông tin tài khoản.' : ' cần được cấp tài khoản hệ thống.');

            $notification = app($manager)->sendToPermission('accounts.users.manage', [
                'type' => 'action_required',
                'priority' => 'high',
                'source_module' => 'human-resource',
                'source_event' => $isIdentitySync ? 'hr.account_identity_sync_requested' : 'hr.account_request_submitted',
                'source_type' => Employee::class,
                'source_id' => (string) $employee->getKey(),
                'sender_user_id' => auth()->id(),
                'sender_name' => auth()->user()?->name,
                'title' => $title,
                'body' => $body,
                'detail_body' => trim((string) ($request['note'] ?? '')) ?: null,
                'action_label' => 'Xử lý yêu cầu',
                'action_url' => $actionUrl,
                'mandatory' => true,
                'manual' => false,
                'metadata' => [
                    'employee_id' => (int) $employee->getKey(),
                    'employee_code' => (string) $employee->employee_code,
                    'request_type' => $type,
                    'workflow_status' => 'pending',
                ],
            ], (array) config('dth-notification-center.integrations.hr_account_request_channels', ['in_app', 'email']));

            return (int) $notification->recipients->count();
        } catch (Throwable) {
            // Notification Center is optional. A delivery failure must never block the HR request itself.
            return 0;
        }
    }

    /** @param array<string, mixed> $request */
    private function notifyRequesterSubmitted(Employee $employee, array $request, int $adminRecipientCount): void
    {
        $requesterUserId = (int) ($request['requested_by_user_id'] ?? 0);
        $manager = 'Dth\NotificationCenter\Services\NotificationManager';
        if ($requesterUserId <= 0 || ! class_exists($manager) || ! Schema::hasTable('dth_notifications')) {
            return;
        }

        try {
            $type = (string) ($request['type'] ?? 'provision_account');
            $isIdentitySync = $type === 'sync_account_identity';
            $resource = 'Dth\HumanResource\Filament\Resources\EmployeeResource';
            $actionUrl = class_exists($resource)
                ? $resource::getUrl('view', ['record' => (int) $employee->getKey()])
                : null;

            app($manager)->send([
                'type' => 'status_update',
                'priority' => $adminRecipientCount > 0 ? 'normal' : 'high',
                'source_module' => 'human-resource',
                'source_event' => $isIdentitySync ? 'hr.account_identity_sync_submitted' : 'hr.account_request_submitted_confirmation',
                'source_type' => Employee::class,
                'source_id' => (string) $employee->getKey(),
                'sender_user_id' => auth()->id(),
                'sender_name' => auth()->user()?->name,
                'title' => $isIdentitySync ? 'Đã gửi yêu cầu đồng bộ tài khoản' : 'Đã gửi yêu cầu cấp tài khoản',
                'body' => trim($employee->employee_code.' · '.$employee->full_name)
                    .($adminRecipientCount > 0 ? ' đã được chuyển tới quản trị viên tài khoản.' : ' đã được lưu nhưng chưa có quản trị viên nhận thông báo.'),
                'detail_body' => trim((string) ($request['note'] ?? '')) ?: null,
                'action_label' => 'Xem nhân viên',
                'action_url' => $actionUrl,
                'mandatory' => false,
                'manual' => false,
                'metadata' => [
                    'employee_id' => (int) $employee->getKey(),
                    'request_type' => $type,
                    'admin_recipient_count' => $adminRecipientCount,
                    'workflow_status' => 'pending',
                ],
            ], [$requesterUserId], ['in_app']);
        } catch (Throwable) {
            // Confirmation notification is best effort only.
        }
    }

    private function markAdminRequestResolved(Employee $employee, string $resolution): void
    {
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

    /** @return array<int> */
    private function protectedUserIds(): array
    {
        if (! Schema::hasTable('account_roles') || ! Schema::hasTable('account_role_user')) {
            return [];
        }

        $keys = (array) config('dth-human-resource.account_integration.protected_role_keys', [
            'super-admin', 'super_admin', 'administrator', 'system_admin',
        ]);

        $ids = DB::table('account_role_user')
            ->join('account_roles', 'account_roles.id', '=', 'account_role_user.role_id')
            ->whereIn('account_roles.key', $keys)
            ->pluck('account_role_user.user_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $bootstrapEmails = (array) config('dth-account-management.bootstrap.administrator_emails', []);
        if ($bootstrapEmails !== []) {
            $ids = [
                ...$ids,
                ...DB::table('users')->whereIn('email', $bootstrapEmails)->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            ];
        }

        if ((bool) config('dth-account-management.bootstrap.oldest_user_is_administrator', false)) {
            $oldestId = DB::table('users')->orderBy('id')->value('id');
            if ($oldestId) {
                $ids[] = (int) $oldestId;
            }
        }

        return array_values(array_unique($ids));
    }

    private function isProtectedUser(int $userId): bool
    {
        return in_array($userId, $this->protectedUserIds(), true);
    }

    private function findUser(int $userId): ?Model
    {
        $model = $this->accountUserModel();

        return $model::query()->find($userId);
    }

    private function accountUserModel(): string
    {
        return class_exists('Dth\\AccountManagement\\Models\\AccountUser')
            ? 'Dth\\AccountManagement\\Models\\AccountUser'
            : (string) config('auth.providers.users.model', \App\Models\User::class);
    }

    private function normalizeName(string $name): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(Str::ascii(trim($name)))) ?: '';
    }

    private function allowsDefinedAbility(string $ability): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $gate = app(GateContract::class);
        $defined = false;
        if (method_exists($gate, 'abilities')) {
            try {
                $defined = array_key_exists($ability, $gate->abilities());
            } catch (Throwable) {
                $defined = false;
            }
        }

        return $defined && $gate->forUser($user)->allows($ability);
    }
}
