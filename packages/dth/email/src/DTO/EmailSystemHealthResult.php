<?php

namespace Dth\Email\DTO;

use Carbon\CarbonImmutable;

final readonly class EmailSystemHealthResult
{
    public function __construct(
        public int $sendingAccounts,
        public int $activeSendingAccounts,
        public int $errorSendingAccounts,
        public int $sendingDomains,
        public int $verifiedSendingDomains,
        public int $failedSendingDomains,
        public ?int $pendingEmailJobs,
        public ?int $failedJobs,
        public string $schedulerHealth,
        public string $queueWorkerHealth,
        public ?CarbonImmutable $schedulerLastSeenAt = null,
        public ?CarbonImmutable $queueWorkerLastSeenAt = null,
    ) {}
}
