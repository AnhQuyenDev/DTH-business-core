<?php

namespace Dth\Email\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EmailSystemHeartbeatService
{
    public const SCHEDULER = 'scheduler';
    public const QUEUE_WORKER = 'queue_worker';

    public function touch(string $component, array $metadata = []): void
    {
        if (! Schema::hasTable('email_system_heartbeats')) {
            return;
        }

        DB::table('email_system_heartbeats')->updateOrInsert(
            ['component' => $component],
            [
                'last_seen_at' => now(),
                'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function lastSeen(string $component): ?CarbonImmutable
    {
        if (! Schema::hasTable('email_system_heartbeats')) {
            return null;
        }

        $value = DB::table('email_system_heartbeats')
            ->where('component', $component)
            ->value('last_seen_at');

        return $value ? CarbonImmutable::parse($value) : null;
    }

    public function health(string $component, int $staleAfterSeconds): string
    {
        $lastSeen = $this->lastSeen($component);

        if (! $lastSeen) {
            return 'unknown';
        }

        return $lastSeen->gte(now()->subSeconds(max(30, $staleAfterSeconds)))
            ? 'healthy'
            : 'unhealthy';
    }
}
