<?php

namespace Dth\Commercial\Support;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Throwable;

final class CommercialAuthorization
{
    public function allows(string $ability, mixed $user = null): bool
    {
        $mode = (string) config('dth-commercial.authorization.mode', 'auto');
        if ($mode === 'off') {
            return true;
        }

        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        foreach (['isAdmin', 'isAdministrator', 'isSuperAdmin'] as $method) {
            if (method_exists($user, $method)) {
                try {
                    if ((bool) $user->{$method}()) {
                        return true;
                    }
                } catch (Throwable) {
                    // Continue with host permissions.
                }
            }
        }

        $permission = (string) config('dth-commercial.authorization.abilities.'.$ability, 'commercial.'.$ability);

        if (method_exists($user, 'hasPermissionTo')) {
            try {
                return (bool) $user->hasPermissionTo($permission);
            } catch (Throwable) {
                // Fall through to Gate / compatibility mode.
            }
        }

        $gate = app(GateContract::class);
        $defined = method_exists($gate, 'has') ? $gate->has($permission) : false;

        if ($mode === 'auto' && ! $defined) {
            return true;
        }

        return $gate->forUser($user)->allows($permission);
    }
}
