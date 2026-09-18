<?php
namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Enums\DataScope;
use Dth\AccountManagement\Models\AccountRole;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AccessControlService
{
    public function __construct(private readonly AccountSettingService $settings) {}

    public function allows(?Authenticatable $user, string $permission): bool
    {
        if (! $user) return false;
        if (! Schema::hasTable('account_roles') || ! Schema::hasTable('account_role_user')) {
            return ! str_starts_with($permission, 'accounts.') || $this->isBootstrapAdministrator($user);
        }

        if (! $this->accountCanAuthenticate($user)) return false;
        if ($this->isSuperAdministrator($user) || $this->isBootstrapAdministrator($user)) return true;

        // Keep legacy DTH modules usable until an administrator explicitly
        // enables permission enforcement after assigning roles.
        if (! str_starts_with($permission, 'accounts.') && ! $this->settings->bool('security.permissions_enforced', false)) {
            return true;
        }

        $permissionId = DB::table('account_permissions')->where('key', $permission)->value('id');
        if (! $permissionId) return false;

        $direct = DB::table('account_user_permissions')
            ->where('user_id', $user->getAuthIdentifier())
            ->where('permission_id', $permissionId)
            ->value('effect');
        if ($direct === 'deny') return false;
        if ($direct === 'allow') return true;

        return DB::table('account_role_user as aru')
            ->join('account_roles as ar', 'ar.id', '=', 'aru.role_id')
            ->join('account_permission_role as apr', 'apr.role_id', '=', 'ar.id')
            ->where('aru.user_id', $user->getAuthIdentifier())
            ->where('ar.is_active', true)
            ->where('apr.permission_id', $permissionId)
            ->exists();
    }

    public function dataScope(?Authenticatable $user): DataScope
    {
        if (! $user || ! Schema::hasTable('account_roles')) return DataScope::Own;
        if ($this->isSuperAdministrator($user) || $this->isBootstrapAdministrator($user)) return DataScope::All;

        $scopes = DB::table('account_roles as ar')
            ->join('account_role_user as aru', 'aru.role_id', '=', 'ar.id')
            ->where('aru.user_id', $user->getAuthIdentifier())
            ->where('ar.is_active', true)
            ->pluck('ar.data_scope')
            ->map(fn ($value) => DataScope::tryFrom((string) $value) ?? DataScope::Own);

        return $scopes->sortByDesc(fn (DataScope $scope) => $scope->rank())->first() ?? DataScope::Own;
    }

    public function isSuperAdministrator(Authenticatable $user): bool
    {
        if (! Schema::hasTable('account_role_user')) return false;
        return DB::table('account_role_user as aru')
            ->join('account_roles as ar', 'ar.id', '=', 'aru.role_id')
            ->where('aru.user_id', $user->getAuthIdentifier())
            ->where('ar.key', 'super-admin')
            ->where('ar.is_active', true)
            ->exists();
    }

    public function isBootstrapAdministrator(Authenticatable $user): bool
    {
        $email = strtolower((string) ($user->email ?? ''));
        $configured = array_map('strtolower', (array) config('dth-account-management.bootstrap.administrator_emails', []));
        if ($email !== '' && in_array($email, $configured, true)) return true;

        if (! config('dth-account-management.bootstrap.oldest_user_is_administrator', true)) return false;
        try {
            $table = $user->getTable();
            return (int) $user->getAuthIdentifier() === (int) DB::table($table)->min($user->getAuthIdentifierName());
        } catch (Throwable) {
            return false;
        }
    }

    private function accountCanAuthenticate(Authenticatable $user): bool
    {
        if (! Schema::hasColumn($user->getTable(), 'account_status')) return true;
        $record = DB::table($user->getTable())->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())->first(['account_status', 'locked_until']);
        if (! $record) return false;
        if ((string) $record->account_status !== 'active') return false;
        if ($record->locked_until && now()->lt($record->locked_until)) return false;
        return true;
    }
}
