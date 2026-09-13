<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Models\MarketingAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

final class MarketingAuditTrailService
{
    /** @param array<string, mixed> $oldValues @param array<string, mixed> $newValues @param array<string, mixed> $metadata */
    public function log(
        string $action,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
    ): void {
        if (! config('dth-marketing.audit.enabled', true)) {
            return;
        }

        try {
            MarketingAuditLog::query()->create([
                'user_id' => Auth::id(),
                'action' => mb_substr($action, 0, 120),
                'auditable_type' => $auditable?->getMorphClass(),
                'auditable_id' => $auditable?->getKey() !== null ? (string) $auditable->getKey() : null,
                'old_values' => $this->safe($oldValues),
                'new_values' => $this->safe($newValues),
                'metadata' => $this->safe($metadata),
                'ip_hash' => $this->ipHash(),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            // Audit must never break the business transaction in a package that
            // can be installed before the hardening migration is applied.
            report($exception);
        }
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    private function safe(array $values): array
    {
        $blocked = [
            'data', 'normalized_data', 'normalized_email', 'normalized_phone',
            'display_name', 'company_name', 'user_agent', 'referrer', 'ip_address',
        ];

        foreach ($blocked as $key) {
            unset($values[$key]);
        }

        return $values;
    }

    private function ipHash(): ?string
    {
        $ip = request()?->ip();
        if (! is_string($ip) || $ip === '') {
            return null;
        }

        $key = (string) config('app.key', 'dth-marketing');

        return hash_hmac('sha256', $ip, $key);
    }
}
