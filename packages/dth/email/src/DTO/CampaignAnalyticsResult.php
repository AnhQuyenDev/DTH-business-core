<?php

namespace Dth\Email\DTO;

final readonly class CampaignAnalyticsResult
{
    public function __construct(
        public int $total,
        public int $pending,
        public int $queued,
        public int $sent,
        public int $delivered,
        public int $opened,
        public int $clicked,
        public int $failed,
        public int $bounced,
        public int $complained,
        public int $suppressed,
        public int $unsubscribed,

        public float $deliveryRate,
        public float $openRate,
        public float $clickRate,
        public float $failureRate,
        public float $unsubscribeRate,
    ) {}
}