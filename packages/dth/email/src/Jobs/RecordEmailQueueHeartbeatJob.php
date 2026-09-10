<?php

namespace Dth\Email\Jobs;

use Dth\Email\Services\EmailSystemHeartbeatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordEmailQueueHeartbeatJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 120;

    public function uniqueId(): string
    {
        return 'dth-email-queue-heartbeat';
    }

    public function handle(EmailSystemHeartbeatService $heartbeats): void
    {
        $heartbeats->touch(EmailSystemHeartbeatService::QUEUE_WORKER, [
            'queue' => (string) config('dth-email.queue', 'emails'),
            'connection' => (string) config('queue.default', 'sync'),
        ]);
    }
}
