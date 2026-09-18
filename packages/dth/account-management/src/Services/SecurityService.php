<?php
namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Models\AccountUser;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Schema;

final class SecurityService
{
    public function __construct(private readonly AccountSettingService $settings, private readonly AuditLogger $audit) {}

    public function onLogin(Login $event): void
    {
        if (! Schema::hasColumn('users', 'last_login_at')) return;
        AccountUser::query()->whereKey($event->user->getAuthIdentifier())->update([
            'last_login_at' => now(), 'last_login_ip' => request()?->ip(), 'failed_login_attempts' => 0,
        ]);
        $this->audit->write('accounts', 'login', $event->user::class, (string) $event->user->getAuthIdentifier(), 'Đăng nhập hệ thống', userId: (int) $event->user->getAuthIdentifier());
    }

    public function onFailed(Failed $event): void
    {
        if (! Schema::hasColumn('users', 'failed_login_attempts')) return;
        $email = strtolower((string) ($event->credentials['email'] ?? ''));
        if ($email === '') return;
        $user = AccountUser::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user) return;
        $attempts = (int) $user->failed_login_attempts + 1;
        $max = $this->settings->int('security.max_failed_logins', (int) config('dth-account-management.security.max_failed_logins', 5));
        $updates = ['failed_login_attempts' => $attempts];
        if ($attempts >= $max) {
            $minutes = $this->settings->int('security.lock_minutes', (int) config('dth-account-management.security.lock_minutes', 30));
            $updates['locked_until'] = now()->addMinutes(max(1, $minutes));
        }
        $user->forceFill($updates)->save();
        $this->audit->write('accounts', 'login_failed', AccountUser::class, (string) $user->id, 'Đăng nhập thất bại', newValues: ['attempts' => $attempts], userId: $user->id);
    }

    public function onLogout(Logout $event): void
    {
        if (! $event->user) return;
        $this->audit->write('accounts', 'logout', $event->user::class, (string) $event->user->getAuthIdentifier(), 'Đăng xuất hệ thống', userId: (int) $event->user->getAuthIdentifier());
    }
}
