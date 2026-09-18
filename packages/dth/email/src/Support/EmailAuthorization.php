<?php

namespace Dth\Email\Support;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Throwable;

/**
 * Optional host-permission bridge for Email-specific reporting abilities.
 *
 * Email remains independently usable when the host has no permission registry:
 * undefined abilities fall back to the existing authenticated-resource checks.
 * When Account Management (or another host) defines these Gate abilities, they
 * are enforced without creating a hard dependency from Email to that package.
 */
final class EmailAuthorization
{
    public function allows(string $ability, mixed $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        $gate = app(GateContract::class);
        $defined = false;

        try {
            if (method_exists($gate, 'has')) {
                $defined = (bool) $gate->has($ability);
            } elseif (method_exists($gate, 'abilities')) {
                $abilities = $gate->abilities();
                $defined = is_array($abilities) && array_key_exists($ability, $abilities);
            }
        } catch (Throwable) {
            $defined = false;
        }

        // Standalone Email has no account permission registry. Preserve the
        // module's existing behavior and let the resource/page authorization
        // decide access in that case.
        if (! $defined) {
            return true;
        }

        return $gate->forUser($user)->allows($ability);
    }

    public function reports(mixed $user = null): bool
    {
        return $this->allows('email.reports', $user);
    }

    public function export(mixed $user = null): bool
    {
        return $this->allows('email.export', $user);
    }
}
