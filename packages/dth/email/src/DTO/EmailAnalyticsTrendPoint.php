<?php

namespace Dth\Email\DTO;

use Carbon\CarbonImmutable;

final readonly class EmailAnalyticsTrendPoint
{
    public function __construct(
        public CarbonImmutable $bucket,
        public int $sent,
        public int $uniqueOpened,
        public int $totalOpens,
        public int $uniqueClicked,
        public int $totalClicks,
        public int $unsubscribed,
        public int $failed,
    ) {}
}
