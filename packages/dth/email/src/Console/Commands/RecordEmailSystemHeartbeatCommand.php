<?php

namespace Dth\Email\Console\Commands;

use Dth\Email\Jobs\RecordEmailQueueHeartbeatJob;
use Dth\Email\Services\EmailSystemHeartbeatService;
use Illuminate\Console\Command;

class RecordEmailSystemHeartbeatCommand extends Command
{
    protected $signature = 'email:health:heartbeat';
    protected $description = 'Record Email scheduler and queue-worker heartbeat probes.';

    public function handle(EmailSystemHeartbeatService $heartbeats): int
    {
        $heartbeats->touch(EmailSystemHeartbeatService::SCHEDULER, [
            'command' => 'email:health:heartbeat',
        ]);

        if ((string) config('queue.default', 'sync') !== 'sync') {
            RecordEmailQueueHeartbeatJob::dispatch()
                ->onQueue((string) config('dth-email.queue', 'emails'));
        } else {
            $heartbeats->touch(EmailSystemHeartbeatService::QUEUE_WORKER, [
                'connection' => 'sync',
            ]);
        }

        return self::SUCCESS;
    }
}
