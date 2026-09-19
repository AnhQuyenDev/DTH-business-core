<?php

namespace Dth\NotificationCenter\Services;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Throwable;

final class NotificationAuthorization
{
    public function allows(string $ability, mixed $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        foreach (['isAdmin', 'isAdministrator', 'isSuperAdmin'] as $method) {
            if (! method_exists($user, $method)) {
                continue;
            }
            try {
                if ((bool) $user->{$method}()) {
                    return true;
                }
            } catch (Throwable) {
                // Continue with the normal Gate / fallback path.
            }
        }

        $gate = app(GateContract::class);
        $defined = method_exists($gate, 'has') ? $gate->has($ability) : false;
        if ($defined) {
            return $gate->forUser($user)->allows($ability);
        }

        $email = strtolower(trim((string) ($user->email ?? '')));
        $fallback = array_map('strtolower', (array) config('dth-notification-center.fallback_administrator_emails', []));

        return $email !== '' && in_array($email, $fallback, true);
    }

    public function canSend(mixed $user = null): bool
    {
        return $this->allows('notifications.send', $user);
    }


    public function canTargetRoles(mixed $user = null): bool
    {
        return $this->allows('notifications.send.roles', $user);
    }

    public function canTargetGroups(mixed $user = null): bool
    {
        return $this->allows('notifications.send.groups', $user);
    }

    public function canTargetDepartments(mixed $user = null): bool
    {
        return $this->allows('notifications.send.departments', $user);
    }

    public function canBroadcast(mixed $user = null): bool
    {
        return $this->allows('notifications.send.broadcast', $user);
    }

    public function canViewAudit(mixed $user = null): bool
    {
        return $this->allows('notifications.audit.view', $user);
    }

    public function canManageTemplates(mixed $user = null): bool
    {
        return $this->allows('notifications.templates.manage', $user);
    }

    public function canManageSystemSettings(mixed $user = null): bool
    {
        return $this->allows('notifications.settings.manage', $user);
    }
}
