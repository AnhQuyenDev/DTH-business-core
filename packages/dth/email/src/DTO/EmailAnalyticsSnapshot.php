<?php

namespace Dth\Email\DTO;

final readonly class EmailAnalyticsSnapshot
{
    public function __construct(
        public int $campaigns,
        public int $recipients,
        public int $messages,
        public int $sent,
        public int $delivered,
        public int $uniqueOpened,
        public int $totalOpens,
        public int $uniqueClicked,
        public int $totalClicks,
        public int $failed,
        public int $bounced,
        public int $complained,
        public int $suppressed,
        public int $unsubscribed,
        public ?float $deliveryRate,
        public float $openRate,
        public float $clickRate,
        public float $clickToOpenRate,
        public float $failureRate,
        public ?float $bounceRate,
        public ?float $complaintRate,
        public float $unsubscribeRate,
    ) {}
}
