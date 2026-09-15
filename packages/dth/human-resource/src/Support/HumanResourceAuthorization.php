<?php

namespace Dth\HumanResource\Support;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Throwable;

final class HumanResourceAuthorization
{
    public function allows(string $ability, mixed $user = null): bool
    {
        $mode = (string) config('dth-human-resource.authorization_mode', 'auto');
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
                    // Host-specific helpers may require additional context.
                }
            }
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

        if ($mode === 'strict' || $defined) {
            return $gate->forUser($user)->allows($ability);
        }

        return true;
    }
}
