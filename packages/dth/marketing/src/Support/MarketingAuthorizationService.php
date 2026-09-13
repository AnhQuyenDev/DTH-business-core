<?php

namespace Dth\Marketing\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Throwable;

final class MarketingAuthorizationService
{
    public function allows(?Authenticatable $user, string $ability): bool
    {
        if ($user === null) {
            return false;
        }

        $mode = (string) config('dth-marketing.authorization.mode', 'auto');
        if ($mode === 'off') {
            return true;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        $permission = (string) config('dth-marketing.authorization.abilities.'.$ability, 'marketing.'.$ability);

        if (method_exists($user, 'hasPermissionTo')) {
            try {
                return (bool) $user->hasPermissionTo($permission);
            } catch (Throwable) {
                // Fall through to Laravel Gate / compatibility mode.
            }
        }

        $gate = app(GateContract::class);
        $defined = false;
        if (method_exists($gate, 'abilities')) {
            try {
                $abilities = $gate->abilities();
                $defined = is_array($abilities) && array_key_exists($permission, $abilities);
            } catch (Throwable) {
                $defined = false;
            }
        }

        if ($mode === 'strict' || $defined) {
            return $gate->forUser($user)->allows($permission);
        }

        // Auto mode preserves compatibility with the current DTH core where no
        // permission registry exists yet, while immediately honoring host Gate
        // abilities or Spatie-style permissions when they are installed.
        return true;
    }

    public function view(?Authenticatable $user): bool
    {
        return $this->allows($user, 'view');
    }

    public function manage(?Authenticatable $user): bool
    {
        return $this->allows($user, 'manage');
    }

    public function reports(?Authenticatable $user): bool
    {
        return $this->allows($user, 'view-reports');
    }

    public function export(?Authenticatable $user): bool
    {
        return $this->allows($user, 'export');
    }

    public function processSubmissions(?Authenticatable $user): bool
    {
        return $this->allows($user, 'process-submissions');
    }

    private function isAdministrator(Authenticatable $user): bool
    {
        foreach (['isAdmin', 'isAdministrator', 'isSuperAdmin'] as $method) {
            if (method_exists($user, $method)) {
                try {
                    if ((bool) $user->{$method}()) {
                        return true;
                    }
                } catch (Throwable) {
                    // Ignore host-specific helpers that require extra context.
                }
            }
        }

        return false;
    }
}
