<?php

namespace Dth\Email\DTO;

use Carbon\CarbonImmutable;

final readonly class TopCampaignResult
{
    public function __construct(
        public int $campaignId,
        public string $name,
        public string $subject,
        public string $status,
        public ?CarbonImmutable $startedAt,
        public int $recipients,
        public int $sent,
        public int $opened,
        public int $clicked,
        public int $unsubscribed,
        public int $failed,
        public float $openRate,
        public float $clickRate,
        public float $clickToOpenRate,
        public float $unsubscribeRate,
        public float $failureRate,
    ) {}
}
