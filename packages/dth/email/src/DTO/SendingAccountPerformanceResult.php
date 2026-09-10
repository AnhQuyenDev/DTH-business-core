<?php

namespace Dth\Email\DTO;

final readonly class SendingAccountPerformanceResult
{
    public function __construct(
        public int $sendingAccountId,
        public string $name,
        public string $provider,
        public string $status,
        public int $campaigns,
        public int $recipients,
        public int $sent,
        public int $opened,
        public int $clicked,
        public int $failed,
        public float $openRate,
        public float $clickRate,
        public float $failureRate,
        public TransportCapabilities $capabilities,
    ) {}
}
