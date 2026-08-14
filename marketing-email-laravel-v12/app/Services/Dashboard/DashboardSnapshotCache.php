<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;

final class DashboardSnapshotCache
{
    /** @return array<string, mixed> */
    public function remember(
        User $user,
        string $dataset,
        string $period,
        Closure $resolver,
    ): array {
        $ttl = max(0, (int) config('performance.dashboard_cache_seconds', 45));

        if ($ttl === 0) {
            return $resolver();
        }

        $key = implode(':', [
            'dashboard',
            'v2',
            app()->getLocale(),
            $user->getKey(),
            preg_replace('/[^a-z0-9_-]/i', '_', $dataset),
            preg_replace('/[^a-z0-9_-]/i', '_', $period),
        ]);

        return Cache::remember($key, now()->addSeconds($ttl), $resolver);
    }
}
