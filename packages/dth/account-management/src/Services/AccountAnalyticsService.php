<?php
namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Models\AccountAuditLog;
use Dth\AccountManagement\Models\AccountInvitation;
use Dth\AccountManagement\Models\AccountRole;
use Dth\AccountManagement\Models\AccountUser;
use Dth\AccountManagement\Models\SessionRecord;
use Illuminate\Support\Facades\Schema;

final class AccountAnalyticsService
{
    public function __construct(private readonly AccountSettingService $settings) {}

    public function snapshot(): array
    {
        $activeMinutes = (int) config('dth-account-management.security.active_session_minutes', 30);
        $cutoff = now()->subMinutes($activeMinutes)->timestamp;
        return [
            'total_users' => AccountUser::query()->count(),
            'active_users' => AccountUser::query()->where('account_status', 'active')->count(),
            'locked_users' => AccountUser::query()->whereNotNull('locked_until')->where('locked_until', '>', now())->count(),
            'roles' => AccountRole::query()->where('is_active', true)->count(),
            'active_sessions' => Schema::hasTable('sessions') ? SessionRecord::query()->where('last_activity', '>=', $cutoff)->count() : 0,
            'pending_invitations' => Schema::hasTable('account_invitations') ? AccountInvitation::query()->where('status', 'pending')->where('expires_at', '>', now())->count() : 0,
            'permissions_enforced' => $this->settings->bool('security.permissions_enforced', false),
            'role_distribution' => AccountRole::query()->withCount('users')->where('is_active', true)->orderByDesc('users_count')->limit(8)->get(),
            'recent_activity' => Schema::hasTable('account_audit_logs') ? AccountAuditLog::query()->with('actor')->latest('created_at')->limit(8)->get() : collect(),
        ];
    }
}
